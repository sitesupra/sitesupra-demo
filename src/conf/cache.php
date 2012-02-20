<?php

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\MemcacheCache;

if ( ! class_exists('Memcache')) {
	$cache = new Doctrine\Common\Cache\ArrayCache();
} else {

	$memcachedHost = 'localhost';
	$memcachedPort = 11211;

	$systemInfo = ObjectRepository::getSystemInfo('');

	$memcache = new Memcache();
	$memcache->addserver($memcachedHost, $memcachedPort, false);
	$status = $memcache->getversion();

	if ($status === false) {
		\Log::fatal("Memcached server $memcachedHost:$memcachedPort not accessible");
		die(SUPRA_ERROR_MESSAGE);
	}

	$cache = new MemcacheCache();
	$cache->setMemcache($memcache);
}

$cache->setNamespace($systemInfo->getSystemId());

ObjectRepository::setDefaultCacheAdapter($cache);
