<?php

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\MemcacheCache;

$memcachedHost = 'localhost';
$memcachedPort = 11211;

$systemInfo = ObjectRepository::getSystemInfo('');

$memcache = new Memcache();
$memcache->addserver($memcachedHost, $memcachedPort);
$status = $memcache->getversion();

if ($status === false) {
	\Log::fatal("Memcached server $memcachedHost:$memcachedPort not accessible");
	die(SUPRA_ERROR_MESSAGE);
}

$cache = new MemcacheCache();
$cache->setMemcache($memcache);
$cache->setNamespace($systemInfo->getSystemId());

ObjectRepository::setDefaultCacheAdapter($cache);
