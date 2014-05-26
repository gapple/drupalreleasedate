<?php
/**
 * @file
 * An example configuration file to set any local settings for the app.
 *
 * Copy to config.php and make any changes there.
 */

// Set the umask so that cache files are group writable.
// umask(0002);

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

/**
 * Configure the sets of criteria for fetching issue counts.
 */
$config['drupal_issues'] = array(
    // Provide values that will be used as the default for all sets.
    'common' => array(
        'status' => array(
            1, // Active
            13, // Needs work
            8, // Needs review
            14, // Reviewed & tested by the community
            15, // Patch (to be ported)
            4, // Postponed
            // 16, // Postponed (maintainer needs more info)
        ),
    ),
    // Provide the separate requests that will be issued, and their parameters.
    'sets' => array(
        'critical_bugs' => array(
            'priorities' => array(400),
            'categories' => array(1),
        ),
        'critical_tasks' => array(
            'priorities' => array(400),
            'categories' => array(2),
        ),
        'major_bugs' => array(
            'priorities' => array(300),
            'categories' => array(1),
        ),
        'major_tasks' => array(
            'priorities' => array(300),
            'categories' => array(2),
        ),
        'normal_bugs' => array(
            'priorities' => array(200),
            'categories' => array(1),
        ),
        'normal_tasks' => array(
            'priorities' => array(200),
            'categories' => array(2),
        ),
    ),
);
