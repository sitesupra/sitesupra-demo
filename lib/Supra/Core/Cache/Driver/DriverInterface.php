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

	/**
	 * Checks existence of an item
	 *
	 * @param $prefix
	 * @param $key
	 * @param int $timestamp
	 * @return mixed
	 */
	public function has($prefix, $key, $timestamp = 0);

	/**
	 * Deletes given entry
	 *
	 * @param $prefix
	 * @param $key
	 * @return mixed
	 */
	public function delete($prefix, $key);

	/**
	 * Clears data on given prefix
	 *
	 * @param null|string $prefix
	 * @return mixed
	 */
	public function clear($prefix = null);

}