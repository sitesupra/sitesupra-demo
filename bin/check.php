<?php

ob_start();
$html = isset($_SERVER['SERVER_NAME']);

$requiredPhpVersion = '5.3.10';

$requiredExtensions = array(
	'ctype',
	'curl',
	'fileinfo',
	'filter',
	'gd',
	'hash',
	'iconv',
	'intl',
	'json',
	'mbstring',
	'memcache',
	'openssl',
	'pcre',
	'pdo',
	'pdo_mysql',
	'posix',
	'session',
	'simplexml',
	'xml',
	'zlib',
	'http',
);

$requiredFunctions = array(
	'imagecreatefromgif',
	'imagecreatefromjpeg',
	'imagecreatefrompng',
	'imagettftext',
);

// This file must be inside webroot. The script check_init.php should copy it there.

$webrootDir = __DIR__;
$supraDir = dirname($webrootDir);

$writableDirectories = array(
	$supraDir . '/log',
	$supraDir . '/tmp',
	$supraDir . '/files',
	$webrootDir . '/files',
	$webrootDir . '/tmp',
	$supraDir . '/lib/Supra/Proxy',
);

$status = array();

$phpVersionStatus = 'OK';

if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
	$phpVersionStatus = "ERR (" . PHP_VERSION . " < $requiredPhpVersion)";
}

$status['PHP']['version'] = $phpVersionStatus;

foreach ($requiredExtensions as $requiredExtension) {
	$loaded = (extension_loaded($requiredExtension) ? 'OK' : 'ERR');
	$status['extension'][$requiredExtension] = $loaded;
}

foreach ($requiredFunctions as $requiredFunction) {
	$exists = (function_exists($requiredFunction) ? 'OK' : 'ERR');
	$status['function'][$requiredFunction] = $exists;
}

function removeBasedir($filename, $baseDir)
{
	if (strpos($filename, $baseDir . DIRECTORY_SEPARATOR) === 0) {
		return substr($filename, strlen($baseDir) + 1);
	} else {
		return $filename;
	}
}

foreach ($writableDirectories as $writableDirectory) {

	$status['writeable'][removeBasedir($writableDirectory, $supraDir)] = 'OK';
	$directoryStatus = &$status['writeable'][removeBasedir($writableDirectory, $supraDir)];

	if ( ! file_exists($writableDirectory)) {
		$directoryStatus = 'ERR (not exists)';
		continue;
	}

	if ( ! is_dir($writableDirectory)) {
		$directoryStatus = 'ERR (not directory)';
		continue;
	}

	if ( ! is_writable($writableDirectory)) {
		$directoryStatus = 'ERR (not writable)';
		continue;
	}

	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($writableDirectory),
			FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS);

	foreach ($iterator as $pathname) {
		if ( ! is_writable($pathname)) {
			$directoryStatus = 'ERR (not writable contents, e.g. ' . removeBasedir($pathname, $supraDir) . ')';
			continue 2;
		}
	}
}

if ($html) {

	header('Content-Type: text/html');

	echo '<table border="1">';

	foreach ($status as $type => $results) {
		foreach ($results as $label => $result) {
			echo "<tr><td>$type</td><td>$label</td><td>$result</td>";
		}
	}

	echo '</table>';

	echo '<form method="post"><input type="submit" name="delete" value="Delete check file" /><form>';

	if (isset($_POST['delete'])) {
		$deleted = unlink(__FILE__);

		if ( ! $deleted) {
			echo '<b>Cannot remove the check file.</b> Please remove manually.';
		} else {
			echo '<b>Done!</b>';
			header('Location: /');
		}
	}
} else {
	header('Content-Type: text/plain');

	foreach ($status as $type => $results) {
		echo "$type:\n";
		foreach ($results as $label => $result) {
			echo "\t$label: $result\n";
		}
	}
}
