<?php
require_once('bootstrap.php');

$app->mount('/', new \DrupalReleaseDate\Controllers\PagesControllerProvider());
$app->mount('/chart', new \DrupalReleaseDate\Controllers\ChartsControllerProvider());
$app->mount('/data', new \DrupalReleaseDate\Controllers\DataControllerProvider());
$app->mount('/cron', new \DrupalReleaseDate\Controllers\CronControllerProvider());

$app->run();
