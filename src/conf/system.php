<?php

use Supra\ObjectRepository\ObjectRepository;

$conf = ObjectRepository::getIniConfigurationLoader('');

$systemInfo = new Supra\Info();
$systemInfo->id = $conf->getValue('system', 'id');
$systemInfo->hostName = $conf->getValue('system', 'host');
$systemInfo->name = $conf->getValue('system', 'name');
$systemInfo->version = $conf->getValue('system', 'version');

ObjectRepository::setDefaultSystemInfo($systemInfo);