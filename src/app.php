<?php
require_once('bootstrap.php');

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
    ));
});

$app->get('/about', function () use ($app) {
    return $app['twig']->render('about.twig', array(

    ));
});

$app->run();
