<?php

namespace Supra\Cache;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\Cache;

/**
 * 
 */
class CacheGroupManager
{
	/**
	 * @var Cache
	 */
	private static $cache;
	
	/**
	 * Local cache
	 * @var array
	 */
	private static $localCache = array();
	
	/**
	 * Constructor 
	 */
	public function __construct()
	{
		if ( ! isset(self::$cache)) {
			self::$cache = ObjectRepository::getCacheAdapter($this);
		}
	}
	
	private function getCacheName($group)
	{
		return __CLASS__ . '_' . $group;
	}
	
	public function getRevision($group)
	{
		if (isset(self::$localCache[$group])) {
			return self::$localCache[$group];
		}
		
		$cacheName = $this->getCacheName($group);
		$value = self::$cache->fetch($cacheName);
		
		if ($value === false) {
			$value = $this->resetRevision($group, $cacheName);
		} else {
			self::$localCache[$group] = $value;
		}
		
		return $value;
	}
	
	public function resetRevision($group)
	{
		$cacheName = $this->getCacheName($group);
		$value = mt_rand();
		self::$cache->save($cacheName, $value);
		
		self::$localCache[$group] = $value;

		return $value;
	}
}
