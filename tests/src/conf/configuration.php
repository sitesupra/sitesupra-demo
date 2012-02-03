<?php

$ini = parse_ini_file(SUPRA_TESTS_CONF_PATH . 'supra.ini', true);
$home = getenv('HOME');

if ( ! empty($home) && file_exists($home . '/supra.ini')) {
	$localIni = parse_ini_file($home . '/supra.ini', true);
	
	foreach ($localIni as $section => $vars) {
		if (isset($ini[$section])) {
			$ini[$section] = array_merge($ini[$section], $vars);
		} else {
			$ini[$section] = $vars;
		}
	}
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'user.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'filestorage.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';
