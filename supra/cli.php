#!/usr/bin/env php
<?php

use Symfony\Component\Debug\Debug;

require_once __DIR__ . '/../vendor/autoload.php';

$input = new \Symfony\Component\Console\Input\ArgvInput();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$env = $input->getParameterOption(array('--env', '-e'), 'cli');
if ($input->hasParameterOption(array('--debug'))) {
	$debug = (bool)$input->getParameterOption(array('--debug'), true);
} else {
	$debug = true;
}

if ($debug) {
	Debug::enable();
}

$app = new SupraApplication($env, $debug);

$container = $app->buildContainer();

$container->getConsole()->run($input, $output);