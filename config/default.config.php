<?php
/**
 * @file
 * An example configuration file to set any local settings for the app.
 *
 * Copy to config.php and make any changes there.
 */

$app['debug'] = false;


$config['twig'] = array(
    'cache' => APPROOT . 'cache/twig',
);

$config['google'] = array();

$config['cronkey'] = '';
