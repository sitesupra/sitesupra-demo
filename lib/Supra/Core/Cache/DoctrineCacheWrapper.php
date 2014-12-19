<?php

namespace Supra\Core\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class DoctrineCacheWrapper extends CacheProvider implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	protected $prefix;

	protected $suffix;

	public function __construct()
	{
		$this->prefix = 'unknown';
		$this->suffix = '';
	}

	/**
	 * @return string
	 */
	public function getSuffix()
	{
		return $this->suffix;
	}

	/**
	 * @param string $suffix
	 */
	public function setSuffix($suffix)
	{
		$this->suffix = $suffix;
	}

	/**
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	protected function getCache()
	{
		return $this->container->getCache();
	}

	protected function doFetch($id)
	{
		return $this->getCache()->fetch($this->prefix, $id.$this->suffix);
	}

	protected function doContains($id)
	{
		return (bool)$this->getCache()->fetch($this->prefix, $id.$this->suffix);
	}

	protected function doSave($id, $data, $lifeTime = 0)
	{
		return $this->getCache()->store($this->prefix, $id.$this->suffix, $data, time(), $lifeTime);
	}

	protected function doDelete($id)
	{
		return $this->getCache()->delete($this->prefix, $id.$this->suffix);
	}

	public function deleteAll()
	{
		return $this->doFlush();
	}

	protected function doFlush()
	{
		return $this->getCache()->clear($this->prefix);
	}

	protected function doGetStats()
	{
		return array();
	}
}
