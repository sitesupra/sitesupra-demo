<?php

$mainIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini');
$mainData = $mainIniParser->getData();

$testsIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini', SUPRA_TESTS_CONF_PATH);
$testsData = $testsIniParser->getData();

$home = getenv('HOME');
$localData = array();

if ( ! empty($home) && file_exists($home . '/supra.ini')) {
	$localIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini', $home);
	$localData = $localIniParser->getData();
	
}

foreach (array($testsData, $localData) as $data) {
	foreach ($data as $section => $vars) {
		if (isset($mainData[$section])) {
			$mainData[$section] = array_merge($mainData[$section], $vars);
		} else {
			$mainData[$section] = $vars;
		}
	}
}

$ini = new \Supra\Configuration\Loader\ArrayIniConfigurationLoader($mainData);
\Supra\ObjectRepository\ObjectRepository::setIniConfigurationLoader('Supra\Tests', $ini);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'user.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'filestorage.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';
