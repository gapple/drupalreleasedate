<?php
require_once('bootstrap.php');

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


$app->get('/', 'DrupalReleaseDate\Controllers\Pages::index')
->after(function(Request $request, Response $response) {
  // Allow caching for one week.
  $response->setMaxAge(604800);
  $response->setSharedMaxAge(604800);
});;
$app->get('about', 'DrupalReleaseDate\Controllers\Pages::about')
->after(function(Request $request, Response $response) {
  // Allow caching for one week.
  $response->setMaxAge(604800);
  $response->setSharedMaxAge(604800);
});;

$chart = $app['controllers_factory'];
$chart->get('/samples', 'DrupalReleaseDate\Controllers\Charts::samples');
$chart->get('/estimates', 'DrupalReleaseDate\Controllers\Charts::estimates');
$chart->get('/distribution', 'DrupalReleaseDate\Controllers\Charts::distribution');
$chart->after(function(Request $request, Response $response) {
  // Allow caching for one week.
  $response->setMaxAge(604800);
  $response->setSharedMaxAge(604800);
});
$app->mount('/chart', $chart);


$data = $app['controllers_factory'];
$data->get('/samples.json', 'DrupalReleaseDate\Controllers\Data::samples');
$data->get('/estimates.json', 'DrupalReleaseDate\Controllers\Data::estimates');
$data->get('/distribution.json', 'DrupalReleaseDate\Controllers\Data::distribution');
$data->after(function(Request $request, Response $response) {
    // Respond as JSONP if necessary
    if (($response instanceof JsonResponse) && $request->get('callback') !== null)
    {
        $response->setCallBack($request->get('callback'));
    }
});
$data->after(function(Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    // Allow caching for one hour.
    $response->setMaxAge(3600);
    $response->setSharedMaxAge(3600);
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
// Check key in request before running cron.
$cron->before(function (Request $request) use ($app) {
    $key = $request->attributes->get('key');
    if (!isset($app['config']['cron.key']) || empty($key) || $key != $app['config']['cron.key']) {
        return new Response(null, 403);
    }
});

$app->mount('/cron', $cron);

$app->run();
