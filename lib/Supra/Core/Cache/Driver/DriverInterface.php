<?php

namespace Supra\Core\Cache\Driver;

interface DriverInterface
{
	/**
	 * Sets cache value
	 *
	 * @param string $prefix
	 * @param string $key
	 * @param mixed $value
	 * @param int $timestamp Unix timestamp
	 * @param int $ttl TTL, 0 means forever
	 * @return mixed
	 */
	public function set($prefix, $key, $value, $timestamp = 0, $ttl = 0);

	/**
	 * Gets value from cache
	 *
	 * @param string $prefix
	 * @param string $key
	 * @param int $timestamp
	 * @return mixed
	 */
	public function get($prefix, $key, $timestamp = 0);
}