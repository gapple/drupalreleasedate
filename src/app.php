<?php
require_once('bootstrap.php');

use Silex\Application;

use DrupalReleaseDate\SampleSet;
use DrupalReleaseDate\MonteCarlo;

$app->get('/', function (Application $app) {

    $estimate = array(
        'value' => 'N/A',
        'note' => 'The latest estimate could not be retrieved',
    );

    $sql = "
        SELECT " . $app['db']->quoteIdentifier('estimate') . "
            FROM " . $app['db']->quoteIdentifier('estimates') . "
            WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
            ORDER BY " . $app['db']->quoteIdentifier('when') . " DESC
    ";
    $result = $app['db']->fetchColumn($sql, array(), 0);

    if ($result == '0000-00-00 00:00:00') {
        $estimate['note'] = 'An estimate could not be calculated with the current data';
    }
    else if ($result) {
        $estimate['value'] = date('F j, Y', strtotime($result . ' +6 weeks'));
        $estimate['note'] = '';
    }

    return $app['twig']->render('index.twig', array(
        'estimate' => $estimate,
    ));
});

$app->get('about', function (Application $app) {
    return $app['twig']->render('about.twig', array(

    ));
});

$app->get('cron', function () {
  return '';
});

// Handle request to update estimate value, protected by key.
$app->get('cron/update-estimate', function () {
    // ignore request without key provided
    return '';
});
$app->get('cron/update-estimate/{key}', function (Application $app, $key) use ($config) {

    // Check key in request
    if (!isset($config['cronkey']) || $key != $config['cronkey']) {
        return '';
    }

    // Run estimate simulation
    $samples = new SampleSet();
    $sql = "
        SELECT " . $app['db']->quoteIdentifier('when') . ", " . $app['db']->quoteIdentifier('critical_bugs') . "," . $app['db']->quoteIdentifier('critical_tasks') . "
            FROM " . $app['db']->quoteIdentifier('samples') . "
            WHERE " . $app['db']->quoteIdentifier('version')  ." = 8
            ORDER BY " . $app['db']->quoteIdentifier('when') . " ASC
    ";
    $results = $app['db']->query($sql);
    while ($result = $results->fetchObject()) {
        $samples->addSample(strtotime($result->when), $result->critical_bugs + $result->critical_tasks);
    }

    set_time_limit(300);

    $monteCarlo = new MonteCarlo($samples);
    $estimateDuration = $monteCarlo->run();

    if ($estimateDuration) {
        $estimate = date('Y-m-d h:i:s', $_SERVER['REQUEST_TIME'] + $estimateDuration);
    }
    else {
        $estimate = '0000-00-00 00:00:00';
    }

     // store in database
     $app['db']->insert('estimates', array(
         '`when`' => date('Y-m-d h:i:s', $_SERVER['REQUEST_TIME']),
         'version' => 8,
         'estimate' => $estimate,
     ));

     return '';
});

$app->run();
