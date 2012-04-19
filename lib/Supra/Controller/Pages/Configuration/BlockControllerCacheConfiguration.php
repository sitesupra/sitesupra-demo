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
	
	/**
	 * If set, the cache will be enabled only if value inside response context
	 * by this name is not empty.
	 * @var string
	 */
	public $enabledByContext;
	
	public function configure()
	{
		
	}
	
	/**
	 * Get cache key where to store the block, return null if not cacheable
	 * @param Localization $page
	 * @param Block $block
	 * @param ResponseContext $context used if cache is context dependent
	 * @return string, null if disabled, empty string if context required
	 */
	public function getCacheKey(Localization $page, Block $block, ResponseContext $context = null)
	{
		// Not possible to determine without the context
		if ($this->isContextDependent() && is_null($context)) {
			return '';
		}
		
		if ( ! $this->enabled) {
			return null;
		}
		
		// No cache if lifetime is negative
		$lifetime = $this->getLifetime();
		if ($lifetime < 0) {
			return null;
		}
		
		if ( ! empty($this->enabledByContext)) {
			$value = $context->getValue($this->enabledByContext);

			if (empty($value)) {
				return null;
			}
		}
		
		$cacheGroups = array();
		$cacheGroupManager = new \Supra\Cache\CacheGroupManager();
		
		// Cache always differs for different block instances for now
		$cacheGroups[] = $block->getId();

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
	
	/**
	 * Whether the cache depends on context (can be found after block prepare stage)
	 * @return boolean
	 */
	private function isContextDependent()
	{
		return ! empty($this->context) || ! empty($this->enabledByContext);
	}
}
