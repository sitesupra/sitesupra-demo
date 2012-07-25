<?php

namespace Supra\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Cache;

class CacheNamespaceWrapper extends CacheProvider
{
	/**
	 * Cache instance
	 * @var Cache
	 */
	protected $cache;
	
	/**
	 * Create namespace name local copy for namespace getter
	 * @var string
	 */
	private $namespace;

	public function __construct(Cache $cache, $namespace = null)
	{
		$this->cache = $cache;
		if ( ! is_null($namespace)) {
			$this->setNamespace($namespace);
		}
	}
	
	/**
	 * @param string $namespace
	 */
	public function setNamespace($namespace)
	{
		$this->namespace = (string) $namespace;
		parent::setNamespace($namespace);
	}
	
	/**
	 * @return string
	 */
	public function getNamespace()
	{
		if (is_null($this->namespace) && ($this->cache instanceof CacheNamespaceWrapper)) {
			return $this->cache->getNamespace();
		}
		
		return $this->namespace;
	}
	
	protected function doContains($id)
	{
		return $this->cache->contains($id);
	}

	protected function doDelete($id)
	{
		return $this->cache->delete($id);
	}

	protected function doFetch($id)
	{
		return $this->cache->fetch($id);
	}

	protected function doSave($id, $data, $lifeTime = false)
	{
		return $this->cache->save($id, $data, $lifeTime);
	}

	public function getIds()
	{
		throw new \LogicException('Method Supra\Cache\CacheNamespaceWrapper::getIds() is not implemented');
	}
	
	public function getStats()
	{
		// TODO: implement
		return null;
	}

	protected function doFlush()
	{
		$this->cache->flushAll();
	}

	protected function doGetStats()
	{
		return $this->cache->getStats();
	}
}
