<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Pages
{

    public function index(Application $app, Request $request)
    {
        return $app['twig']->render('index.twig', array(
            'scripts' => array(
                '//code.jquery.com/jquery-2.0.2.min.js',
            ),
        ));
    }

    public function about(Application $app, Request $request)
    {
        return $app['twig']->render('about.twig', array());
    }
}
