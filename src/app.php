<?php
require_once('bootstrap.php');

use Silex\Application;


$app->get('/', 'DrupalReleaseDate\Controllers\Pages::index');
$app->get('about', 'DrupalReleaseDate\Controllers\Pages::about');


$cron = $app['controllers_factory'];
$cron->get('/', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');

// Handle request to update estimate value, protected by key.
$cron->get('/update-estimate', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
$cron->get('/update-estimate/{key}', 'DrupalReleaseDate\Controllers\Cron::updateEstimate');

$app->mount('/cron', $cron);

$app->run();
