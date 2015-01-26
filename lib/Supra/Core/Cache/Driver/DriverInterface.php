<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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