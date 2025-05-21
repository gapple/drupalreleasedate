<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$config = array();
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once(__DIR__ . '/../config/config.php');
}
$app['config'] = $config;

$app['config.dir'] = __DIR__ . '/../config';

if (empty($config['db']) || empty($config['db']['dbname'])) {
    throw new \Exception('Database configuration is missing.');
}

$app->register(
    new Silex\Provider\DoctrineServiceProvider(),
    array(
        'db.options' => $config['db'],
    )
);

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

if (isset($config['http_cache']) && $config['http_cache'] !== false) {
    $app->register(
        new Silex\Provider\HttpCacheServiceProvider(),
        array(
            'http_cache.cache_dir' => __DIR__ . '/../cache/http',
            'http_cache.esi'       => null,
            'http_cache.options'   => (isset($config['http_cache']) && is_array($config['http_cache']))? $config['http_cache'] : array(),
        )
    );
}

$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path'    => __DIR__ . '/../templates',
        'twig.options' => isset($config['twig']) ? $config['twig'] : array(),
    )
);

// Set config as a global variable for templates.
$app['twig'] = $app->share(
    $app->extend('twig',
        function ($twig, $app) use ($config) {
            $twig->addGlobal('config', $config);
            return $twig;
        }
    )
);

return $app;
