<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Charts
{

    public function samples(Application $app, Request $request)
    {
        return $app['twig']->render('charts/samples.twig', array(
            'scripts' => array(
                '//code.jquery.com/jquery-2.0.2.min.js',
                'https://www.google.com/jsapi',
            ),
        ));
    }

    public function estimates(Application $app, Request $request)
    {
        return $app['twig']->render('charts/estimates.twig', array(
            'scripts' => array(
                '//code.jquery.com/jquery-2.0.2.min.js',
                'https://www.google.com/jsapi',
            ),
        ));
    }

    public function distribution(Application $app, Request $request)
    {
        return $app['twig']->render('charts/distribution.twig', array(
            'scripts' => array(
                '//code.jquery.com/jquery-2.0.2.min.js',
                'https://www.google.com/jsapi',
            ),
        ));
    }
}
