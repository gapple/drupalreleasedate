<?php

use Symfony\Component\Console\Application;

$app = require_once('bootstrap.php');

$console = new Application('DrupalReleaseDate', '0.1.0');
$console->setDispatcher($app['dispatcher']);

$installation = new \DrupalReleaseDate\Installation($app);
$console->add(new \DrupalReleaseDate\Console\InstallCommand($installation));
$console->add(new \DrupalReleaseDate\Console\UpdateCommand($installation));

return $console;
