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
	 * Get application configuration collection as array
	 *
	 * @return array
	 */
	public function toArray() 
	{
		return array_values($this->collection);
	}

	/**
	 * Add configuration
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $appPath
	 * @param string $iconPath 
	 */
	public function addConfiguration($id, $title, $appPath, $iconPath = null) 
	{
		$item = array(
			'id' => $id,
			'title' => $title,
			'path' => $appPath,
			'icon' => $iconPath
		);
		$this->collection[$id] = $item;
	}
	
}
