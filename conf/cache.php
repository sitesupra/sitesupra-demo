<?php

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\MemcacheCache;

$systemInfo = ObjectRepository::getSystemInfo('');
$cache = null;

// Try memcached, APC, fallback to file cache
if (class_exists('Memcache')) {
	$memcachedHost = 'localhost';
	$memcachedPort = 11211;

	$memcache = new Memcache();
	$memcache->addserver($memcachedHost, $memcachedPort, false);
	$status = $memcache->getversion();

	if ($status === false) {
		\Log::fatal("Memcached server $memcachedHost:$memcachedPort not accessible");
		die(SUPRA_ERROR_MESSAGE);
	}

	$cache = new MemcacheCache();
	$cache->setMemcache($memcache);
} elseif (function_exists('apc_store')) {
	$cache = new Doctrine\Common\Cache\ApcCache();
} else {
	$cache = new Supra\Cache\FileCache();
}

$cache->setNamespace($systemInfo->getSystemId());
ObjectRepository::setDefaultCacheAdapter($cache);
