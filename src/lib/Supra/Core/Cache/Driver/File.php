<?php

namespace Supra\Core\Cache\Driver;

class File implements DriverInterface
{
	/**
	 * Filesystem root folder
	 *
	 * @var string
	 */
	protected $prefix;

	public function __construct($prefix)
	{
		if (!is_dir($prefix)) {
			throw new \Exception(sprintf('Directory "%s" does not exist', $prefix));
		}

		if (!is_writable($prefix)) {
			throw new \Exception(sprintf('Directory "%s" is not writable', $prefix));
		}

		$this->prefix = $prefix;
	}

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
	public function set($prefix, $key, $value, $timestamp = 0, $ttl = 0)
	{
		$data = serialize(array(
			'timestamp' => $timestamp,
			'ttl' => $ttl == 0 ? 0 : time() + $ttl,
			'data' => $value
		));

		$file = $this->getFilename($prefix, $key);

		$dir = dirname($file);

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		file_put_contents($file, $data);
	}

	/**
	 * Gets value from cache
	 *
	 * @param string $prefix
	 * @param string $key
	 * @param int $timestamp
	 * @return mixed
	 */
	public function get($prefix, $key, $timestamp = 0)
	{
		$file = $this->getFilename($prefix, $key);

		if (!is_readable($file)) {
			return false;
		}

		$data = unserialize(file_get_contents($file));

		if ($data['ttl'] != 0 && $data['ttl'] < time()) {
			return false;
		}

		if ($timestamp != 0 && $timestamp > $data['timestamp']) {
			return false;
		}

		return $data['data'];
	}

	protected function getFilename($prefix, $key)
	{
		if (!is_scalar($key)) {
			$key = serialize($key);
		}

		$key = md5($key);

		$folder = substr($key, 0, 2);

		$name = substr($key, 2);

		return implode(DIRECTORY_SEPARATOR,
			array(
				$this->prefix,
				$prefix,
				$folder,
				$name
			)
		) . $this->getExtension();
	}

	protected function getExtension()
	{
		return '.cache';
	}
}