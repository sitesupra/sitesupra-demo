<?php

$filename = $argv[1];

if ( ! file_exists($filename)) {
	echo "File $filename does not exist";
	die(253);
}

$baseDir = __DIR__ . '/../../../..';
$checkDir = $baseDir . '/../../../src/webroot';

$pre = $pre1 = realpath($baseDir);
$preLength = strlen($pre);
$pre2 = null;

// use project's webroot folder if supra7 bound as a symlink
if (file_exists($baseDir) && is_dir($baseDir)) {
	$check1 = realpath($baseDir . '/cms');
	$check2 = realpath($checkDir . '/cms');

	if ($check1 === $check2) {
		$pre = $pre2 = realpath($checkDir);
	}
}

$fullName = realpath($filename);
$fullNameCss = preg_replace('/.less$/', '', $fullName);

if (strpos($fullName, $pre1 . '/') === 0) {
	$q = substr($fullNameCss, strlen($pre1));
} elseif ($pre2 && strpos($fullName, $pre2 . '/') === 0) {
	$q = substr($fullNameCss, strlen($pre2));
} else {
	echo "File $fullName is not inside any webroot";
	die(252);
}

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
