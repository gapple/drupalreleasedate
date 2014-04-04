<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DataControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/samples.json', 'DrupalReleaseDate\Controllers\Data::samples');
        $controllers->get('/changes.json', 'DrupalReleaseDate\Controllers\Data::changes');
        $controllers->get('/estimates.json', 'DrupalReleaseDate\Controllers\Data::estimates');
        $controllers->get('/distribution.json', 'DrupalReleaseDate\Controllers\Data::distribution');

        $controllers
            ->after(function (Request $request, Response $response) {
                // Respond as JSONP if necessary
                if (($response instanceof JsonResponse) && $request->get('callback') !== null) {
                    $response->setCallBack($request->get('callback'));
                }

                $response->headers->set('Access-Control-Allow-Origin', '*');

                // Allow caching for one hour.
                $response->setMaxAge(3600);
                $response->setSharedMaxAge(3600);
            })
            ->after(function (Request $request, Response $response) {
                // Compress the response if supported by client.
                $response->setVary('Accept-Encoding');

                if (strpos($request->headers->get('Accept-Encoding'), 'gzip') !== false) {
                    $response->setContent(gzencode($response->getContent()));
                    $response->headers->set('Content-Encoding', 'gzip');
                }
            }, Application::LATE_EVENT);

        return $controllers;
    }
}
