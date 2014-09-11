<?php

$apc = function_exists('apc_store');

if ( ! empty($_GET['flush']) && $apc) {
	apc_clear_cache('user');
}

$cache = true;
$checkFileModificationTime = true;
$checkFileModificationTimeForIncludedLessCss = true;

include 'combo.php';
