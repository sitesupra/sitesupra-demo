<?php

ini_set('display_errors', 'Off');

$q = @$q ? : $_SERVER['QUERY_STRING'];
$apc = function_exists('apc_store');

$files = explode('&', $q);

// Maybe in future might use this...
//if (preg_match('/^[0-9a-f]{32}$/', $files[0])) {
//	$hash = array_shift($files);
//}

if ( ! sizeOf($files)) {
//    echo('<strong>Error:</strong> No files found.');
	exit;
}

if (count($files) > 100) {
//	echo('<strong>Error:</strong> File count limit exceeded.');
	die(1);
}

// Cache settings for production
if ( ! isset($cache)) {
	$cache = true;
	$checkFileModificationTime = false;
	$checkFileModificationTimeForIncludedLessCss = false;
}

$css = strpos($files[0], '.css') !== false ? true : false;
$ext = ($css ? 'css' : 'js');
$extLength = ($css ? 3 : 2);
$lessCss = true;
$webrootDir = @$pre ? : $_SERVER['DOCUMENT_ROOT'];
$webrootDir = $webrootDir . '/';
$srcDir = $webrootDir . '../';
$baseDir = $srcDir . '../';

// if will need to store in webroot...
//$cacheDir = $webrootDir . '/tmp';
$cacheDir = $srcDir . '/tmp';

$version = __FILE__ . '/' . @file_get_contents($baseDir . '/VERSION');
$versionId = @file_get_contents($baseDir . '/VERSION');
if (empty($versionId)) {
	$versionId = '';
}
$versionKey = base_convert(substr(md5($versionId), 0, 8), 16, 36);

foreach ($files as &$file) {

	$file = str_replace(array('Y$', 'S$'), array('/cms/lib/yui.3.5.0/build/', '/cms/lib/supra/build/'), $file);

	// Only CSS/JS allowed
	if (substr($file, - $extLength - 1) !== '.' . $ext) {
		die(2);
	}

	if (strpos($file, '..') !== false) {
		die(3);
	}

	//$expectedStart = '/cms/';
	// Only files from webroot/cms folder allowed
	//if (strpos($file, $expectedStart) !== 0) {
	//	die(4);
	//}
}
unset($file);

$eTag = getEtag($files);
header('ETag: ' . $eTag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $eTag === $_SERVER['HTTP_IF_NONE_MATCH']) {
	header('HTTP/1.0 304 Not Modified');
	die(5);
}

$out = $cache ? getCache($eTag) : '';

if ( ! $out) {
	$out = writeFiles($files, $eTag);
}

function getCache($eTag)
{
	global $apc, $cacheDir;
	$out = '';
	if ($apc) {
		$out = apc_fetch('combo-' . $eTag);
	}
	if ( ! $out) {
		if (is_file($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/' . $eTag)) {
			$out = @file_get_contents($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/' . $eTag);
			if ($apc) {
				apc_store('combo-' . $eTag, $out, 1800);
			}
		}
	}
	return $out;
}

function getEtag($files)
{
	global $css, $checkFileModificationTime, $version;
	$cacheSource = array($version);

	if ($checkFileModificationTime) {
		$cacheSource[] = filemtime(__FILE__);
	}

	foreach ($files as $file) {
		if ($checkFileModificationTime) {
			$cacheSource = array_merge($cacheSource, getFileMtime($file));
		} else {
			$cacheSource[] = $file;
		}
	}
	return md5(implode(',', $cacheSource));
}

function writeFiles($files, $eTag)
{
	global $build, $apc, $cache, $version, $css, $ext, $cacheDir;
	$outFile = '';
	$out = '';
	foreach ($files as $file) {
		$outFile = getFileContent($file);

		$out .= $outFile . "\n";
	}

	if ($cache) {
		if ($apc) {
			apc_store('combo-' . $eTag, $out, 1800);
		}

		$outDirname = $cacheDir . '/yui/' . substr($eTag, 0, 2);

		@mkdir($outDirname, 0777, true);

		$tmpFilename = tempnam($outDirname, 'tmp-');
		@file_put_contents($tmpFilename, $out);

		$outFilename = $outDirname . '/' . $eTag;

		@rename($tmpFilename, $outFilename);

		@chmod($outDirname, 0777);
		@chmod($outFilename, 0666);
	}

	return $out;
}

function getFileMtime($file)
{
	global $css, $webrootDir, $lessCss, $checkFileModificationTimeForIncludedLessCss;

	$cacheSource = array();

	$files = array($webrootDir . $file);

	// Try searching for .less file
	if ($css) {
		$lessFile = $webrootDir . $file . '.less';

		if ($lessCss && file_exists($lessFile)) {

			if ($checkFileModificationTimeForIncludedLessCss) {
				$lessPhp = __DIR__ . '/../lessphp/SupraLessC.php';
				require_once $lessPhp;
				$less = new SupraLessCFileList($lessFile);
				$less->setRootDir($webrootDir);
				$less->parse();
				$files = $less->getFileList();
			} else {
				$files = array($lessFile);
			}
		}
	}

	foreach ($files as $file) {

		if ( ! file_exists($file)) {
			error404($file);
		}

		$cacheSource[] = $file;
		$cacheSource[] = filemtime($file);
	}

	return $cacheSource;
}

function getFileContent($file)
{
	global $css, $webrootDir, $lessCss, $versionKey;

	$outFile = null;

	// Try searching for .less file
	if ($css) {
		$lessFile = $webrootDir . $file . '.less';

		if ($lessCss && file_exists($lessFile)) {
			$lessPhp = __DIR__ . '/../lessphp/SupraLessC.php';
			require_once $lessPhp;
			$less = new SupraLessC($lessFile);
			$less->formatterName = 'compressed';
			$less->setRootDir($webrootDir);
			
			$less->setVariables(array('version' => $versionKey));
			
			$outFile = $less->parse();
		}
	}

	if (is_null($outFile)) {

		if ( ! file_exists($webrootDir . $file)) {
			error404($webrootDir . $file);
		}

		$outFile = file_get_contents($webrootDir . $file);
	}

	if ($css) {
		//Add path for images which are in same folder as CSS file
		$dir = dirname($file);
		$outFile = preg_replace("/url\([\"|\']?(?!data:image)([^\/\.\"\'][^\)]*?)[\"\']?\)/", "url(\"$dir/$1\")", $outFile);
	}

	return $outFile;
}

function error404($filename)
{
	if (isset($_SERVER['SERVER_NAME'])) {
		header("HTTP/1.0 404 Not Found");
	} else {
		echo("File $filename does not exist\n");
	}
	die(6);
}

if ($css) {
	header('Content-Type: text/css');
} else {
	header('Content-Type: application/x-javascript');
}

//header('Cache-Control: max-age=315360000');
//header('Expires: '.date('r', time() + (60 * 60 * 24 * 365 * 10)));
//header('Age: 0');
echo($out);
//exit;
