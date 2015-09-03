<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/FetchCommand.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new FetchCommand());
$application->run();
