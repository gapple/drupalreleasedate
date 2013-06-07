<?php
require_once('bootstrap.php');

use Silex\Application;


$app->get('/', 'DrupalReleaseDate\Controllers\Pages::index');
$app->get('about', 'DrupalReleaseDate\Controllers\Pages::about');

$chart = $app['controllers_factory'];
$chart->get('/samples', 'DrupalReleaseDate\Controllers\Charts::samples');
$chart->get('/estimates', 'DrupalReleaseDate\Controllers\Charts::estimates');
$app->mount('/chart', $chart);


$data = $app['controllers_factory'];
$data->get('/samples.json', 'DrupalReleaseDate\Controllers\Data::samples');
$data->get('/estimates.json', 'DrupalReleaseDate\Controllers\Data::estimates');
$app->mount('/data', $data);


$cron = $app['controllers_factory'];
$cron->get('/', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');

// Handle request to update estimate value, protected by key.
$cron->get('/update-estimate', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
$cron->get('/update-estimate/{key}', 'DrupalReleaseDate\Controllers\Cron::updateEstimate');

$app->mount('/cron', $cron);

$app->run();
