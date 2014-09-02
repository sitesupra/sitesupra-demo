#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new SupraApplication();

$container = $app->buildContainer();

$input = new \Symfony\Component\Console\Input\ArgvInput();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$container['console.application']->run($input, $output);