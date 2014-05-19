<?php

$app = require_once('bootstrap.php');

$app->mount('/', new \DrupalReleaseDate\Controllers\PagesControllerProvider());
$app->mount('/chart', new \DrupalReleaseDate\Controllers\ChartsControllerProvider());
$app->mount('/data', new \DrupalReleaseDate\Controllers\DataControllerProvider());
$app->mount('/cron', new \DrupalReleaseDate\Controllers\CronControllerProvider());

// If the Symfony2 Reverse Proxy service was enabled and loaded, use it instead.
if (isset($config['http_cache']) && $config['http_cache'] !== false && !empty($app['http_cache'])) {
    $app['http_cache']->run();
} else {
    $app->run();
}
