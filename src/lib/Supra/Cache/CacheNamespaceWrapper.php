<?php

namespace Supra\Cache;

use Doctrine\Common\Cache\AbstractCache;
use Doctrine\Common\Cache\Cache;
use Supra\ObjectRepository\ObjectRepository;

class CacheNamespaceWrapper extends AbstractCache
{

	/**
	 * Cache instance
	 * @var Cache
	 */
	protected $cache;

	public function __construct(Cache $cache)
	{
		$this->cache = $cache;
	}

	protected function _doContains($id)
	{
		return $this->cache->contains($id);
	}

	protected function _doDelete($id)
	{
		return $this->cache->delete($id);
	}

	protected function _doFetch($id)
	{
		return $this->cache->fetch($id);
	}

	protected function _doSave($id, $data, $lifeTime = false)
	{
		return $this->cache->save($id, $data, $lifeTime);
	}

	public function getIds()
	{
		throw new \LogicException('Method Supra\Cache\CacheNamespaceWrapper::getIds() is not implemented');
	}

}