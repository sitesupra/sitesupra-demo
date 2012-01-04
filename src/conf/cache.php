<?php

use Supra\ObjectRepository\ObjectRepository;

$systemInfo = ObjectRepository::getSystemInfo('');

//$cache = new \Doctrine\Common\Cache\MemcacheCache();
//$memcache = new \Memcache();
//$memcache->addserver('127.0.0.1');
//$cache->setMemcache($memcache);
$cache = new \Doctrine\Common\Cache\ArrayCache();
$cache->setNamespace($systemInfo->getSystemId());

ObjectRepository::setDefaultCacheAdapter($cache);
