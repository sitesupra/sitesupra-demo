<?php

namespace Supra\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * File cache implementation
 */
class FileCache extends CacheProvider
{
	/**
	 * Generate filename for cache and create folder if requested
	 * @param string $id
	 * @param boolean $createFolder
	 * @return string
	 */
	private function getFileName($id, $createFolder = false)
	{
		$hash = md5($id);

		$folder = SUPRA_TMP_PATH . substr($hash, 0, 2) . '/' . substr($hash, 2, 2);

		if ($createFolder && ! is_dir($folder)) {
			mkdir($folder, 0755, true);
		}

		return $folder . '/' . substr($hash, 4) . '.cache';
	}

	/**
	 * @param string $id
	 * @return boolean
	 */
	protected function doContains($id)
	{
		return file_exists($this->getFileName($id));
	}

	/**
	 * @param string $id
	 * @return boolean
	 */
	protected function doDelete($id)
	{
		return unlink($this->getFileName($id));
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	protected function doFetch($id)
	{
		$result = @file_get_contents($this->getFileName($id));

		if ( ! $result) {
			return false;
		}

		$expiration = (int) substr($result, 0, 10);
		$data = substr($result, 10);
		unset($result);

		if ($expiration > 0 && $expiration < time()) {
			return false;
		}

		$data = unserialize($data);

		return $data;
	}

	/**
	 * 
	 */
	protected function doFlush()
	{
		foreach (glob(SUPRA_TMP_PATH . '/*/*/*.cache') as $file) {
			unlink($file);
		}
		
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
		$data = serialize($data);
		$expiration = '';
		
		if ($lifeTime > 0) {
			$expiration = time() + $lifeTime;
			$expiration = (string) $expiration;
		}
		
		// 10 characters are for expiration time
		$expiration = str_pad($expiration, 10, '0', STR_PAD_LEFT);
		
		$result = file_put_contents($this->getFileName($id, true), $expiration . $data);
		
		return (bool) $result;
	}

}