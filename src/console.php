<?php
use DrupalReleaseDate\Console;
use Cilex\Provider\Console\Adapter\Silex\ConsoleServiceProvider;

$app = require_once('bootstrap.php');

$app->register(new ConsoleServiceProvider(), array(
    'console.name' => 'DrupalReleaseDate',
    'console.version' => '0.1.0',
));

$console = $app['console'];

$console->add(new Console\CronCommand());
$console->add(new Console\InstallCommand());
$console->add(new Console\UpdateCommand());

return $console;
