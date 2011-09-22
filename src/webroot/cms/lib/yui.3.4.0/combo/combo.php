<?php
$q = $_SERVER['QUERY_STRING'];
$sha1 = sha1($q);
$apc = function_exists('apc_store');

if (!empty($_GET['flush']) && $apc) {
    apc_clear_cache('user');
}

$files = explode('&', $q);
if (!sizeOf($files)) {
    echo('<strong>Error:</strong> No files found.');
    exit;
}

$css = strpos($files[0], '.css') !== false ? true : false;
$cache = false;
$pre = '../../../../';
$tag = '';
$version = '3.4.0';

$eTag = getEtag($files);
header('ETag: ' . $eTag);
if ($eTag === $_SERVER['HTTP_IF_NONE_MATCH']) {
	header('HTTP/1.0 304 Not Modified');
	die();
}

$out = $cache ? getCache($sha1) : '';

if (!$out) {
    $out = writeFiles($files, $sha1);
}

function getCache($sha1) {
    global $apc;
	$out = '';
    if ($apc) {
        $out = apc_fetch('combo-'.$sha1);
    }
    if (!$out) {
        if (is_file('/tmp/yuidev/cache/'.$sha1[0].'/'.$sha1)) {
            $out = @file_get_contents('/tmp/yuidev/cache/'.$sha1[0].'/'.$sha1);
            if ($apc) {
                apc_store('combo-'.$sha1, $out, 1800);
            }
        }
    }
    return $out;
}

function getEtag($files)
{
	global $pre;
	$times = array();
	$times[] = filemtime(__FILE__);
	foreach ($files as $k => $file) {
		if (@is_file($pre.$file)) {
			$times[] = filemtime($pre.$file);
		}
	}
	return md5(implode(',', $times));
}

function writeFiles($files, $sha1) {
    global $pre, $tag, $build, $apc, $cache, $version, $css;
    $outFile = '';
    $out = '';
    foreach ($files as $k => $file) {
        if (@is_file($pre.$file)) {
            $outFile = @file_get_contents($pre.$file);
			
			if ($css) {
				//Add path for images which are in same folder as CSS file
				$dir = dirname($file);
				$outFile = preg_replace("/url\(([^\/]*)\)/", "url($dir/$1)", $outFile);
			}
			
			$out .= $outFile . "\n";
        }
    }
    $out = str_replace('@VERSION@', $version, $out);
    $out = str_replace('@BUILD@', $build, $out);
	
	if ($cache) {
	    if ($apc) {
	        apc_store('combo-'.$sha1, $out, 1800);
	    }
	    @mkdir('/tmp/yuidev/cache/'.$sha1[0].'/', 0777, true);
	    @file_put_contents('/tmp/yuidev/cache/'.$sha1[0].'/'.$sha1, $out);
	}
	
    return $out;
}

if (strpos($files[0], '.css') !== false) {
	header('Content-Type: text/css');
} else {
	header('Content-Type: application/x-javascript');
}

//header('Cache-Control: max-age=315360000');
//header('Expires: '.date('r', time() + (60 * 60 * 24 * 365 * 10)));
//header('Age: 0');
echo($out);
exit;
