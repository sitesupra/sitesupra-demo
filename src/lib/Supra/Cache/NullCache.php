<?php

namespace Supra\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Null cache implementation
 */
class NullCache extends CacheProvider
{
	/**
	 * @param string $id
	 * @return boolean
	 */
	protected function doContains($id)
	{
		return false;
	}

	/**
	 * @param string $id
	 * @return boolean
	 */
	protected function doDelete($id)
	{
		return true;
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	protected function doFetch($id)
	{
		return false;
	}

	/**
	 * 
	 */
	protected function doFlush()
	{
		return true;
	}

	/**
	 * 
	 */
	protected function doGetStats()
	{
		return null;
	}

	/**
	 * @param string $id
	 * @param mixed $data
	 * @param integer $lifeTime
	 * @return boolean
	 */
	protected function doSave($id, $data, $lifeTime = false)
	{
		return true;
	}

}