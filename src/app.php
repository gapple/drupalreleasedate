<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = require_once('bootstrap.php');

$app->mount('/', new \DrupalReleaseDate\Controllers\PagesControllerProvider());
$app->mount('/chart', new \DrupalReleaseDate\Controllers\ChartsControllerProvider());
$app->mount('/data', new \DrupalReleaseDate\Controllers\DataControllerProvider());
$app->mount('/cron', new \DrupalReleaseDate\Controllers\CronControllerProvider());

$app->after(function (Request $request, Response $response) {
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-Xss-Protection', '1; mode=block');
});

// If the Symfony2 Reverse Proxy service was enabled and loaded, use it instead.
if (isset($config['http_cache']) && $config['http_cache'] !== false && !empty($app['http_cache'])) {
    return $app['http_cache'];
} else {
    return $app;
}
