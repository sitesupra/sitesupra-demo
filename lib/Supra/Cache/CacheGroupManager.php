<?php

namespace Supra\Cache;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\PageController;

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
	
	/**
	 * Generates query result cache ID for the query dependent on one or more cache groups
	 * @param Query $query
	 * @param mixed $groups
	 */
	public function configureQueryResultCache(Query $query, $groups)
	{
		// Cache only for public schema
		$em = $query->getEntityManager();
		$publicEm = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
		
		if ($em !== $publicEm) {
			return;
		}
		
		$query->useResultCache(true);
		
		$cacheProfile = $query->getQueryCacheProfile();
		list($cacheKey, $realCacheKey) = $cacheProfile->generateCacheKeys(
				$query->getSQL(), 
				$query->getParameters(), 
				$query->getParameterTypes());
		
		// Add group revision now
		$groups = (array) $groups;
		sort($groups);
		foreach ($groups as $group) {
			$revision = $this->getRevision($group);
			$realCacheKey .= "&rev[$group]=$revision";
		}
		
		// Hash it
		$cacheKey = sha1($realCacheKey);
		$query->setResultCacheId($cacheKey);
	}
}
