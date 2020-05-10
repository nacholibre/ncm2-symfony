#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../command/AutocompleteCommand.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new AutocompleteCommand());

$application->run();
