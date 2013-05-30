<?php
require_once APPROOT . 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

$config = array();
if (file_exists(APPROOT . 'config/config.php')) {
    require_once(APPROOT . 'config/config.php');
}

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => APPROOT . '/templates',
    'twig.options' => isset($config['twig'])? $config['twig'] : array(),
));

// Set config as a global variable for templates.
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) use ($config) {
    $twig->addGlobal('config', $config);
    return $twig;
}));
