<?php

namespace Supra\Cms;

/**
 * CmsApplicationCollection
 *
 */
class CmsApplicationConfiguration {

	/**
	 * CmsApplicationConfiguration instance
	 *
	 * @var CmsApplicationConfiguration
	 */
	protected static $instance;

	/**
	 * Configuration set
	 *
	 * @var array
	 */
	protected $collection = array();

	/**
	 * Get instance
	 *
	 * @return CmsApplicationConfiguration
	 */
	public static function getInstance() 
	{
		if ( ! self::$instance instanceof CmsApplicationConfiguration) {
			self::$instance = new CmsApplicationConfiguration();
		}
		return self::$instance;
	}

	/**
	 * Get all application configuration collection as an array of objects
	 *
	 * @return array
	 */
	public function getArray() 
	{
		return array_values($this->collection);
	}

	/**
	 * Add one application configuration
	 *
	 * @param ApplicationConfiguration $appConfig
	 */
	public function addConfiguration($appConfig) 
	{
		if (( ! $appConfig instanceof ApplicationConfiguration)
			|| empty($appConfig->id)
		) {
			throw new \RuntimeException('Invalid CMS application configuration');
		}
		$id = $appConfig->id;
		$this->collection[$id] = $appConfig;
	}

	/**
	 * Get one application configuration
	 *
	 * @param string $appId
	 * @return ApplicationConfiguration
	 */
	public function getConfiguration($appId) 
	{
		if (isset($this->collection[$appId])) {
			return $this->collection[$appId];
		}
	}
	
}
