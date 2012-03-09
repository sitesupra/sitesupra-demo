<?php

$input = fopen('php://stdin', 'r');

echo "Source path: ";
$from = trim(fgets($input));
$from = rtrim($from, '/') . '/';

echo "Target path: ";
$to = trim(fgets($input));
$to = rtrim($to, '/') . '/';

$_from = escapeshellarg($from);
$_to = escapeshellarg($to);

// Get directories and files in SVN
$dirs = `svn list -R $_from`;
$dirs = explode("\n", trim($dirs));

$skip = null;

$adds = array();

foreach ($dirs as $dir) {
	$dir = '/' . trim($dir, '/');

	if (dirname($dir) === $skip || strpos($dir . '/', $skip) === 0) {
		echo "Skipping $dir\n";
		continue;
	}

	// GOTO label
	question_copy:
	echo "Copy $dir Y/N/S [Y]: ";
	$answer = strtolower(trim(fgets($input)));

	if ($answer == 'y' || $answer === '') {
		if (is_dir($from . $dir)) {
			mkdir($to . $dir, 0755, true);
			$adds[] = $to . $dir;
		} else {
			copy($from . $dir, $to . $dir);
			$adds[] = $to . $dir;
		}
	} elseif ($answer == 's') {
		$skip = dirname($dir);
	} elseif ($answer == 'n') {
		$skip = rtrim($dir, '/');
	} else {
		goto question_copy;
	}
}

// Do SVN add
question_svn_add:
echo "Run SVN add Y/N [Y]: ";
$answer = strtolower(trim(fgets($input)));

if ($answer == 'y' || $answer === '') {
	foreach ($adds as $add) {
		$_add = escapeshellarg($add);
		`svn add --force $_add`;
	}
	echo "Added\n";
} elseif ($answer == 'n') {
	goto end;
} else {
	goto question_svn_add;
}

$props = `svn pl -R $_from`;
$props = explode("\n", trim($props));
$fileName = null;
$fileProperties = array();

foreach ($props as $propLine) {
	$propLine = trim($propLine);
	$matches = null;
	if (preg_match('/\'(.*)\'/', $propLine, $matches)) {
		$fileName = $matches[1];
		$fileName = substr($fileName, strlen($from));
		$fileProperties[$fileName] = array();
	} else {
		$fileProperties[$fileName][] = $propLine;
	}
}

foreach ($fileProperties as $file => $properties) {
	
	// Skip
	if ( ! file_exists($to . $file)) {
		continue;
	}
	
	$_fileTo = escapeshellarg($to . $file);
	$_fileFrom = escapeshellarg($from . $file);
	foreach ($properties as $property) {
		$_property = escapeshellarg($property);

		// not interested
		if ($property == 'svn:mime-type') {
			continue;
		}

		$value = trim(`svn pg $_property $_fileFrom`);
		
		$_value = escapeshellarg($value);
		$command = "svn ps $_property $_value $_fileTo";

		question_svn_prop:
		$questionValue = preg_replace('/^/m', "\t", $value);
		echo "SVN property [$property] on [$to$file]\n\n$questionValue\n\n";
		echo "Y/N [Y]: ";
		$answer = strtolower(trim(fgets($input)));

		if ($answer == 'y' || $answer === '') {
			`$command`;
		} elseif ($answer == 'n') {
			continue;
		} else {
			goto question_svn_prop;
		}
	}
}

end:

echo "Complete\n";
