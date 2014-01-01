<?php
require_once('bootstrap.php');

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

$app->mount('/', new \DrupalReleaseDate\Controllers\PagesControllerProvider());
$app->mount('/chart', new \DrupalReleaseDate\Controllers\ChartsControllerProvider());
$app->mount('/data', new \DrupalReleaseDate\Controllers\DataControllerProvider());
$app->mount('/cron', new \DrupalReleaseDate\Controllers\CronControllerProvider());

$app->run();
