<?php
require_once('bootstrap.php');

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


$app->get('/', 'DrupalReleaseDate\Controllers\Pages::index');
$app->get('about', 'DrupalReleaseDate\Controllers\Pages::about');

$chart = $app['controllers_factory'];
$chart->get('/samples', 'DrupalReleaseDate\Controllers\Charts::samples');
$chart->get('/estimates', 'DrupalReleaseDate\Controllers\Charts::estimates');
$chart->get('/distribution', 'DrupalReleaseDate\Controllers\Charts::distribution');
$app->mount('/chart', $chart);


$data = $app['controllers_factory'];
$data->get('/samples.json', 'DrupalReleaseDate\Controllers\Data::samples');
$data->get('/estimates.json', 'DrupalReleaseDate\Controllers\Data::estimates');
$data->get('/distribution.json', 'DrupalReleaseDate\Controllers\Data::distribution');

$data->after(function(Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
});
$app->mount('/data', $data);


$cron = $app['controllers_factory'];
$cron->get('/', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
// Handle request to update estimate value, protected by key.
$cron->get('/update-estimate', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
$cron->get('/update-estimate/{key}', 'DrupalReleaseDate\Controllers\Cron::updateEstimate');
// Handle request to get latest issue counts, protected by key.
$cron->get('/fetch-counts', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
$cron->get('/fetch-counts/{key}', 'DrupalReleaseDate\Controllers\Cron::fetchCounts');

$app->mount('/cron', $cron);

$app->run();
