<?php
namespace DrupalReleaseDate\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Pages
{

    public function index(Application $app, Request $request)
    {
        return $app['twig']->render('pages/index.twig', array());
    }

    public function about(Application $app, Request $request)
    {
        return $app['twig']->render('pages/about.twig', array());
    }
}
