<?php

namespace Supra\Statistics\GoogleAnalytics\Authentication\Storage;

/**
 * 
 */
class WriteableIniStorage implements StorageInterface
{
	/**
	 * @var string
	 */
	const SECTION = 'google_analytics';
	
	/**
	 * @var \Supra\Configuration\Loader\WriteableIniConfigurationLoader
	 */
	private $ini;
	
	/**
	 * @throws \RuntimeException
	 */
	public function __construct($iniLoader = null)
	{
		if (is_null($iniLoader)) {
			$iniLoader = \Supra\ObjectRepository\ObjectRepository::getIniConfigurationLoader($this);
		}
		
		if ( ! $iniLoader instanceof \Supra\Configuration\Loader\WriteableIniConfigurationLoader) {
			throw new \RuntimeException('Received configuration loader must be an instance of WriteableIniConfigurationLoader');
		}
		
		$this->ini = $iniLoader;
	}
	
	public function get($key, $default = null)
	{
		return $this->ini->getValue(self::SECTION, $key, $default);
	}
	
	public function set($key, $value)
	{
		return $this->ini->setValue(self::SECTION, $key, $value);
	}
	
	public function flush()
	{
		$this->ini->write();
	}
}