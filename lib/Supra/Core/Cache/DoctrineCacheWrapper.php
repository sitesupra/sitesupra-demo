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
