<?php

namespace Supra\Form;

use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Cache\Cache;
use Supra\Cache\CacheNamespaceWrapper;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Wrapper of supra cache for form metadata cache
 */
class FormClassMetadataCache implements CacheInterface
{
	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * 
	 */
	public function __construct()
	{
		$this->cache = new CacheNamespaceWrapper(
				ObjectRepository::getCacheAdapter($this),
				__CLASS__
				);
	}

	public function has($class)
	{
		return $this->cache->contains($class);
	}

	public function read($class)
	{
		return $this->cache->fetch($class);
	}

	public function write(ClassMetadata $metadata)
	{
		$this->cache->save($metadata->name, $metadata);
	}

}
