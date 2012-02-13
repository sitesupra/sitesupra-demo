<?php

namespace Supra\Cache;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Query;

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
	 * @return string
	 */
	public function getQueryResultCacheId(Query $query, $groups)
	{
		$groups = (array) $groups;
		
		// The cache ID generation code is not public in Doctrine ORM 2.1, so 
		// had to duplicate it here.
		// TODO: this can be changed when Doctrine 2.2 will be used
		$em = $query->getEntityManager();
		$params = $query->getParameters();
		foreach ($params AS $key => $value) {
			if (is_object($value) && $em->getMetadataFactory()->hasMetadataFor(get_class($value))) {
				if ($em->getUnitOfWork()->getEntityState($value) == UnitOfWork::STATE_MANAGED) {
					$idValues = $em->getUnitOfWork()->getEntityIdentifier($value);
				} else {
					$class = $em->getClassMetadata(get_class($value));
					$idValues = $class->getIdentifierValues($value);
				}
				$params[$key] = $idValues;
			} else {
				$params[$key] = $value;
			}
		}

		$sql = $query->getSql();
		$hints = $query->getHints();
		ksort($hints);
		$key = implode(";", (array) $sql) . var_export($params, true) .
				var_export($hints, true) . "&hydrationMode=" . $query->getHydrationMode();
		
		// Add group revision now
		sort($groups);
		foreach ($groups as $group) {
			$revision = $this->getRevision($group);
			$key .= "&rev[$group]=$revision";
		}
		
		// Hash it
		$key = sha1($key);
		
		return $key;
	}
}
