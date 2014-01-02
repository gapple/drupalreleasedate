<?php
/**
 * @file
 * An example configuration file to set any local settings for the app.
 *
 * Copy to config.php and make any changes there.
 */

/**
 * Enable debugging features.
 */
$app['debug'] = false;

/**
 * Configure options for the Doctrine database connection.
 * @see http://silex.sensiolabs.org/doc/providers/doctrine.html
 */
$config['db'] = array(
    'dbname' => '',
    'user' => '',
    'password' => '',
);

/**
 * Configure options for the Symfony2 Reverse Proxy.
 * @see http://silex.sensiolabs.org/doc/providers/http_cache.html
 *
 * Set to a boolean value to simply enable or disable the cache, or provide an
 * array of options.
 * If this option is ommitted or null, the cache will not be enabled.
 */
$config['http_cache'] = array(
    'debug' => $app['debug'],
);

/**
 * Configure options for the Twig template engine.
 * @see http://silex.sensiolabs.org/doc/providers/twig.html
 */
$config['twig'] = array(
    'cache' => APPROOT . 'cache/twig',
);

/**
 * Configure options to be used when making requests with Guzzle.
 */
$config['guzzle'] = array(
    'userAgent' => '',
);

/**
 * Configure options for Google services.
 */
$config['google'] = array(
    'analytics' => null, // Analytics API key.
);

/**
 * A private key to protect cron tasks from being run by a third-party.
 * A value must be set in order for tasks to run.
 */
$config['cron.key'] = '';

/**
 * Time limit for running estimation, in seconds.
 */
$config['estimate.timeout'] = 600;
/**
 * How many iterations to run in the Monte Carlo simulation.
 */
$config['estimate.iterations'] = 100000;
