<?php
/**
 * @file
 * An example configuration file to set any local settings for the app.
 *
 * Copy to config.php and make any changes there.
 */

$app['debug'] = false;

// For connection options
// @see http://silex.sensiolabs.org/doc/providers/doctrine.html
$config['db'] = array(

);

// For available options
// @see http://silex.sensiolabs.org/doc/providers/twig.html
$config['twig'] = array(
    'cache' => APPROOT . 'cache/twig',
);

$config['google'] = array();

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
