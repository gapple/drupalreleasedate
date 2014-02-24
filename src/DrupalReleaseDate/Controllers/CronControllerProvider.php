<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CronControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
        // Handle request to update estimate value
        $controllers->get('/update-estimate', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
        $controllers->get('/update-estimate/{key}', 'DrupalReleaseDate\Controllers\Cron::updateEstimate');
        // Handle request to get latest issue counts
        $controllers->get('/fetch-counts', 'DrupalReleaseDate\Controllers\Cron::emptyResponse');
        $controllers->get('/fetch-counts/{key}', 'DrupalReleaseDate\Controllers\Cron::fetchCounts');

        // Check key in request before running cron.
        $controllers->before(
            function (Request $request) use ($app) {
                $key = $request->attributes->get('key');
                if (!isset($app['config']['cron.key']) || empty($key) || $key != $app['config']['cron.key']) {
                    return new Response(null, 403);
                }
            }
        );

        return $controllers;
    }
}
