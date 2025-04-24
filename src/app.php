<?php

use Symfony\Component\HttpFoundation\JsonResponse;
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

    if (stripos($response->headers->get('Content-Type'), 'text/html') !== false) {
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' code.jquery.com www.google.com www.google-analytics.com; " .
                "style-src 'self' 'unsafe-inline' netdna.bootstrapcdn.com www.google.com ajax.googleapis.com; " .
                "img-src 'self' s3.amazonaws.com www.google-analytics.com stats.g.doubleclick.net; " .
                "connect-src 'self' www.drupal.org www.google-analytics.com; " .
                "report-uri https://rprt.gapple.ca/r"
        );
        $response->headers->set(
            'Referrer-Policy',
            'no-referrer-when-downgrade'
        );
    }
});

// If the Symfony2 Reverse Proxy service was enabled and loaded, use it instead.
if (isset($config['http_cache']) && $config['http_cache'] !== false && !empty($app['http_cache'])) {
    return $app['http_cache'];
} else {
    return $app;
}
