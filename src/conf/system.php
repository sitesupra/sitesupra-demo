<?php

use Supra\ObjectRepository\ObjectRepository;

$conf = ObjectRepository::getIniConfigurationLoader('');

$systemInfo = new Supra\Info();
$systemInfo->hostName = $conf->getValue('system', 'host');
$systemInfo->systemId = $conf->getValue('system', 'system_id');

ObjectRepository::setDefaultSystemInfo($systemInfo);