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

$config['cronkey'] = '';
