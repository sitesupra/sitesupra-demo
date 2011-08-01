<?php

ini_set('error_reporting', 'E_NONE');

include_once("settings.php");
include_once("lib/YUIFileUtil.php");
include_once("lib/YUIHeaderUtil.php");
include_once("lib/YUICombo.php");

$cache = false;

$config = array(
	"cache" => $cache,
	"cacheCombo" => $cache,
	// "gzip" => false
);

$get = explode('&', $_SERVER['QUERY_STRING']);
$get = array_fill_keys(array_values($get), '');
unset($get['']);

$combo = new YUICombo($get, $config);

$combo->loadModules();
?>