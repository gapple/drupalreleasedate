<?php
define('TEST_RESOURCE_PATH', __DIR__ . '/resources');

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('DrupalReleaseDate\Tests', __DIR__);
