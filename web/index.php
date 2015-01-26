<?php

require_once __DIR__ . '/../vendor/autoload.php';

$supra = new SupraApplication('dev', true);

//this should be refactored to single call, for the sake of prettiness
$supra->buildContainer();
$supra->boot();
$response = $supra->handleRequest();
$response->send();
$supra->shutdown();