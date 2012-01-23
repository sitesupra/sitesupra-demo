<?php

$q = $_SERVER['QUERY_STRING'];
$apc = function_exists('apc_store');

if (!empty($_GET['flush']) && $apc) {
    apc_clear_cache('user');
}

$files = explode('&', $q);
$files = array_unique($files);

if (!sizeOf($files)) {
//    echo('<strong>Error:</strong> No files found.');
    exit;
}

if (count($files) > 100) {
//	echo('<strong>Error:</strong> File count limit exceeded.');
	die();
}

$css = strpos($files[0], '.css') !== false ? true : false;
$ext = ($css ? 'css' : 'js');
$cache = false;
$pre = realpath('../../../../');
$preLength = strlen($pre);
$checkFileModificationTime = true;
$cacheDir = dirname($pre) . '/tmp';
//$version = '3.4.0';

foreach ($files as $file) {
	// Only CSS/JS allowed
	if ( ! preg_match('/\.' . $ext . '$/', $file)) {
		die();
	}
	
	if (strpos($file, '..') !== false) {
		die();
	}
	
	$expectedStart = '/cms/';

	// Only files from webroot/cms folder allowed
	if (strpos($file, $expectedStart) !== 0) {
		die();
	}
}

$eTag = getEtag($files);
header('ETag: ' . $eTag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $eTag === $_SERVER['HTTP_IF_NONE_MATCH']) {
	header('HTTP/1.0 304 Not Modified');
	die();
}

$out = $cache ? getCache($eTag) : '';

if (!$out) {
    $out = writeFiles($files, $eTag);
}

function getCache($eTag) {
    global $apc, $cacheDir;
	$out = '';
    if ($apc) {
        $out = apc_fetch('combo-'.$eTag);
    }
    if (!$out) {
        if (is_file($cacheDir . '/yui/' . substr($eTag, 0, 2) .'/'.$eTag)) {
            $out = @file_get_contents($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/'.$eTag);
            if ($apc) {
                apc_store('combo-'.$eTag, $out, 1800);
            }
        }
    }
    return $out;
}

function getEtag($files)
{
	global $css, $pre, $checkFileModificationTime;
	$cacheSource = array();
	$cacheSource[] = filemtime(__FILE__);
	foreach ($files as $file) {
		if ($checkFileModificationTime) {
			$cacheSource += getFileMtime($file);
		}
	}
	return md5(implode(',', $cacheSource));
}

function writeFiles($files, $eTag) {
    global $pre, $build, $apc, $cache, $version, $css, $ext, $checkFileModificationTime, $cacheDir;
    $outFile = '';
    $out = '';
    foreach ($files as $file) {
		$outFile = getFileContent($file);

		$out .= $outFile . "\n";
    }
	
	if ($cache) {
	    if ($apc) {
	        apc_store('combo-'.$eTag, $out, 1800);
	    }
	    @mkdir($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/', 0777, true);
	    @file_put_contents($cacheDir . '/yui/' . substr($eTag, 0, 2) . '/'.$eTag, $out);
	}
	
    return $out;
}

function getFileMtime($file)
{
	global $css, $pre;
	
	$cacheSource = array();
	
	$files = array($pre . $file);
	
	// Try searching for .less file
	if ($css) {
		$lessFile = $pre . $file . '.less';
		
		if (file_exists($lessFile)) {
			$lessPhp = $pre . '/cms/lib/supra/lessphp/SupraLessC.php';
			require_once $lessPhp;
			$less = new SupraLessCFileList($lessFile);
			$less->setRootDir($pre);
			$less->parse();
			$files = $less->getFileList();
		}
	}
	
	foreach ($files as $file) {
		$cacheSource[] = $file;
		$cacheSource[] = filemtime($file);
	}
	
	return $cacheSource;
}

function getFileContent($file)
{
	global $css, $pre;
	
	$outFile = null;
	
	// Try searching for .less file
	if ($css) {
		$lessFile = $pre . $file . '.less';
		
		if (file_exists($lessFile)) {
			$lessPhp = $pre . '/cms/lib/supra/lessphp/SupraLessC.php';
			require_once $lessPhp;
			$less = new SupraLessC($lessFile);
			$less->setRootDir($pre);
			$outFile = $less->parse();
		}
	}

	if (is_null($outFile)) {
		$outFile = @file_get_contents($pre . $file);
	}

	if ($css) {
		//Add path for images which are in same folder as CSS file
		$dir = dirname($file);
		$outFile = preg_replace("/url\(([^\/]*)\)/", "url($dir/$1)", $outFile);
	}

	return $outFile;
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
exit;
