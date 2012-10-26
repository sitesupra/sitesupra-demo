<?php

$filename = $argv[1];

if ( ! file_exists($filename)) {
	echo "File $filename does not exist";
	die(253);
}

$fullName = realpath($filename);
$fullNameCss = preg_replace('/.less$/', '', $fullName);
$pre = dirname($fullNameCss);
$q = basename($fullNameCss);

$cache = false;

ob_start();
require __DIR__ . '/combo.php';
$data = ob_get_contents();
ob_end_clean();

if (empty($data)) {
	echo 'Fail on ', $q, PHP_EOL;
	die(254);
}

file_put_contents($fullNameCss, $data);
