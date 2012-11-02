#!/usr/bin/env php
<?php

// When used with symbolic links
$rootDir = dirname(dirname(__DIR__));
$baseName = basename(__FILE__);

// Otherwise
if (realpath($rootDir . '/bin/' . $baseName) !== realpath(__FILE__)) {
	$rootDir = dirname(__DIR__);
}

$random = rand();
$file = 'check_' . $random . '.php';
$dest = $rootDir . '/src/webroot/' . $file;

$result = copy(__DIR__ . '/check.php', $dest);

if ($result) {
	echo "Open file $file in your browser.\n";
} else {
	echo "No permission to copy the file in the webroot.\n";
}
