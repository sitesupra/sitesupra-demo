<?php

$q = $_SERVER['QUERY_STRING'];
$apc = function_exists('apc_store');

$files = explode('&', $q);

if ( ! sizeOf($files)) {
//    echo('<strong>Error:</strong> No files found.');
	exit;
}

if (count($files) > 100) {
//	echo('<strong>Error:</strong> File count limit exceeded.');
	die();
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
$pre = $pre ?: $_SERVER['DOCUMENT_ROOT'];

//$pre =  __DIR__ . '/../../../../';
$cacheDir = __DIR__ . '/../../../../../tmp';
$version = __FILE__ . '/' . @file_get_contents(__DIR__ . '/../../../../../../VERSION');

foreach ($files as $file) {
	// Only CSS/JS allowed
	if (substr($file, - $extLength - 1) !== '.' . $ext) {
		die();
	}

	if (strpos($file, '..') !== false) {
		die();
	}

	//$expectedStart = '/cms/';
	// Only files from webroot/cms folder allowed
	//if (strpos($file, $expectedStart) !== 0) {
	//	die();
	//}
}

$eTag = getEtag($files);
header('ETag: ' . $eTag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $eTag === $_SERVER['HTTP_IF_NONE_MATCH']) {
	header('HTTP/1.0 304 Not Modified');
	die();
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
		@mkdir($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/', 0777, true);
		@file_put_contents($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/' . $eTag, $out);
		@chmod($cacheDir . '/yui/' . substr($eTag, 0, 2), 0777);
		@chmod($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/' . $eTag, 0666);
	}

	return $out;
}

function getFileMtime($file)
{
	global $css, $pre, $lessCss, $checkFileModificationTimeForIncludedLessCss;
	
	$cacheSource = array();

	$thisPre = $pre;

	if (strpos($file, '/cms-local/') === 0) {
		$thisPre = realpath('../../../../../../../src/webroot');
	} 
	
	$files = array($thisPre . $file);

	// Try searching for .less file
	if ($css) {
		$lessFile = $thisPre . $file . '.less';

		if ($lessCss && file_exists($lessFile)) {
			
			if ($checkFileModificationTimeForIncludedLessCss) {
				$lessPhp = $thisPre . '/cms/lib/supra/lessphp/SupraLessC.php';
				require_once $lessPhp;
				$less = new SupraLessCFileList($lessFile);
				$less->setRootDir($thisPre);
				$less->parse();
				$files = $less->getFileList();
			} else {
				$files = array($lessFile);
			}
		}
	}

	foreach ($files as $file) {

		if ( ! file_exists($file)) {
			error404();
		}

		$cacheSource[] = $file;
		$cacheSource[] = filemtime($file);
	}

	return $cacheSource;
}

function getFileContent($file)
{
	global $css, $pre, $lessCss;

	$outFile = null;
	
	$thisPre = $pre;
	
	if (strpos($file, '/cms-local/') === 0) {
		$thisPre = realpath('../../../../../../../src/webroot');
	} else {
		$thisPre = $pre;
	}
	// Try searching for .less file
	if ($css) {
		$lessFile = $thisPre . $file . '.less';

		if ($lessCss && file_exists($lessFile)) {
			$lessPhp = $thisPre . '/cms/lib/supra/lessphp/SupraLessC.php';
			require_once $lessPhp;
			$less = new SupraLessC($lessFile);
			$less->setRootDir($pre);
			$outFile = $less->parse();
		}
	}

	if (is_null($outFile)) {

		if ( ! file_exists($thisPre . $file)) {
			error404();
		}

		$outFile = file_get_contents($thisPre . $file);
	}

	if ($css) {
		//Add path for images which are in same folder as CSS file
		$dir = dirname($file);
		$outFile = preg_replace("/url\(([^\/\.][^\)]*?)\)/", "url($dir/$1)", $outFile);
	}

	return $outFile;
}

function error404()
{
	header("HTTP/1.0 404 Not Found");
	die();
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
