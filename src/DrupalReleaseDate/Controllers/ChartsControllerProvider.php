<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChartsControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/samples', 'DrupalReleaseDate\Controllers\Charts::samples');
        $controllers->get('/estimates', 'DrupalReleaseDate\Controllers\Charts::estimates');
        $controllers->get('/distribution', 'DrupalReleaseDate\Controllers\Charts::distribution');

        $controllers->after(
            function (Request $request, Response $response) {
                // Allow caching for one week.
                $response->setMaxAge(604800);
                $response->setSharedMaxAge(604800);
            }
        );

        return $controllers;
    }
}
