<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DrupalReleaseDate\Controllers\Cron as CronController;

class CronControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['cron.controller'] = $app->share(function() use ($app) {
            return new CronController($app['db']);
        });

        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'cron.controller:emptyResponse');
        // Handle request to update estimate value
        $controllers->get('/update-estimate', 'cron.controller:emptyResponse');
        $controllers->get('/update-estimate/{key}', 'cron.controller:updateEstimate');
        // Handle request to get latest issue counts
        $controllers->get('/fetch-counts', 'cron.controller:emptyResponse');
        $controllers->get('/fetch-counts/{key}', 'cron.controller:fetchCounts');

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
