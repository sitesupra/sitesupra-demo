<?php

$iniLoaders = array();

$mainIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini');
$iniLoaders[] = $mainIniParser;

$testsIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini', SUPRA_TESTS_CONF_PATH);
$iniLoaders[] = $testsIniParser;

$home = getenv('HOME');
$localData = array();

if ( ! empty($home) && file_exists($home . '/supra.ini')) {
	$localIniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini', $home);
	$iniLoaders[] = $localIniParser;
}

$ini = new \Supra\Configuration\Loader\CombinedIniConfigurationLoader($iniLoaders);

//foreach (array($testsData, $localData) as $data) {
//	foreach ($data as $section => $vars) {
//		if (isset($mainData[$section])) {
//			$mainData[$section] = array_merge($mainData[$section], $vars);
//		} else {
//			$mainData[$section] = $vars;
//		}
//	}
//}

\Supra\ObjectRepository\ObjectRepository::setIniConfigurationLoader('Supra\Tests', $ini);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'user.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'filestorage.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';
