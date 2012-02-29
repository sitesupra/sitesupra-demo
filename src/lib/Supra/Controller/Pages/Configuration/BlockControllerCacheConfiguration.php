<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Response\ResponseContext;

/**
 * Content cache configuration
 */
class BlockControllerCacheConfiguration implements ConfigurationInterface
{
	/**
	 * Whether cache is enabled
	 * @var boolean
	 */
	public $enabled = true;
	
	/**
	 * Does the content differs by request page
	 * @var boolean
	 */
	public $global = false;
	
	/**
	 * Cache groups
	 * @var string[]
	 */
	public $groups = array();
	
	/**
	 * 
	 * @var array
	 */
	public $context = array();
	
	/**
	 * Lifetime of the cache. Empty means cache does not expires.
	 * @var string
	 */
	public $lifetime;
	
	public function configure()
	{
		
	}
	
	/**
	 * Get cache key where to store the block, return null if not cacheable
	 * @param Localization $page
	 * @param Block $block
	 * @param ResponseContext $context used if cache is context dependant
	 * @return string 
	 */
	public function getCacheKey(Localization $page, Block $block, ResponseContext $context = null)
	{
		if ( ! $this->enabled) {
			return;
		}
		
		// No cache if lifetime is negative
		$lifetime = $this->getLifetime();
		if ($lifetime < 0) {
			return;
		}
		
		if ( ! empty($this->context) && is_null($context)) {
			return;
		}
		
		$cacheGroups = array();
		$cacheGroupManager = new \Supra\Cache\CacheGroupManager();
		
		// Cache always differs for different block instances for now
		$cacheGroups[] = $block->getId();;

		if ( ! $this->global) {
			$cacheGroups[] = $page->getId();
		}
		
		foreach ((array) $this->groups as $group) {
			$cacheGroups[] = $cacheGroupManager->getRevision($group);
		}
		
		foreach ((array) $this->context as $contextKey) {
			$cacheGroups[] = $context->getValue($contextKey);
		}
		
		$cacheKey = __CLASS__ . '_' . implode('_', $cacheGroups);
		
		return $cacheKey;
	}
	
	/**
	 * Get cache lifetime. Zero for "no lifetime", -1 to disable the cache
	 * @return int
	 */
	public function getLifetime()
	{
		if (empty($this->lifetime)) {
			$lifetime = 0;
		} elseif (is_numeric($this->lifetime)) {
			$lifetime = (int) $this->lifetime;
		} elseif (is_string($this->lifetime)) {
			$lifetime = strtotime($this->lifetime) - time();
		}
		
		$lifetime = max($lifetime, -1);
		
		return $lifetime;
	}
}
