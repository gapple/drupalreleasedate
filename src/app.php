<?php
require_once('bootstrap.php');

use Silex\Application;


$app->get('/', 'DrupalReleaseDate\Controllers\Pages::index');
$app->get('about', 'DrupalReleaseDate\Controllers\Pages::about');

$app->get('cron', function () {
  return '';
});


// Handle request to update estimate value, protected by key.
$app->get('cron/update-estimate', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
$app->get('cron/update-estimate/{key}', 'DrupalReleaseDate\Controllers\Cron::updateEstimate');

$app->run();
