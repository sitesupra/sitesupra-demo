<?php

namespace Supra\Core\Cache;

use Supra\Core\Cache\Driver\DriverInterface;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class Cache implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var DriverInterface
	 */
	protected $driver;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param \Supra\Core\Cache\Driver\DriverInterface $driver
	 */
	public function setDriver(DriverInterface $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Smart fetch from a cache. $prefix and $key are (in most cases) combined yet can be used by cache driver for
	 * sharding. Default can be a scalar value OR callable; it will be called only on cache miss (thus saving you some
	 * code execution).
	 *
	 * Additionally, if $timestamp (last modification timestamp of you cache source) is provided and is newer than
	 * saved by cache driver, cache miss will be considered.
	 *
	 * $respectDebug means that, in debug mode, a cache miss will always be considered, disabled by default.
	 *
	 * Optional $ttl can be specified, although the driver may nor respect it.
	 *
	 * @param string $prefix
	 * @param mixed $key
	 * @param null $default
	 * @param int $timestamp
	 * @param int $ttl
	 * @param bool $respectDebug
	 * @return mixed
	 */
	public function fetch($prefix, $key, $default = null, $timestamp = 0, $ttl = 0, $respectDebug = false)
	{
		if ($respectDebug && false) {
			//@todo: bind to kernel debug here
			return $this->store($prefix, $key, $default, $timestamp, $ttl);
		}

		if ($value = $this->driver->get($prefix, $key, $timestamp)) {
			return $value;
		}

		return $this->store($prefix, $key, $default, $timestamp, $ttl);
	}

	/**
	 * Generate, store and return
	 *
	 * @param $prefix
	 * @param $key
	 * @param $default
	 * @param $timestamp
	 * @param $ttl
	 * @return mixed
	 */
	protected function store($prefix, $key, $default, $timestamp, $ttl)
	{
		$value = $this->processDefault($default);

		if ($value === null) {
			return $value;
		}

		$this->driver->set($prefix, $key, $value, $timestamp, $ttl);

		return $value;
	}

	/**
	 * Resolve possible callable provided as $default
	 *
	 * @param $default
	 * @return mixed
	 */
	protected function processDefault($default)
	{
		if (is_callable($default)) {
			return call_user_func($default);
		}

		return $default;
	}
}