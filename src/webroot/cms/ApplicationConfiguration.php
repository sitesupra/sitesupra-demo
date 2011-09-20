<?php

namespace Supra\Cms;

/**
 * ApplicationConfiguration
 *
 */
class ApplicationConfiguration 
{

	/**
	 * Application ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Application title
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * Application icon path
	 *
	 * @var string
	 */
	public $icon;
	
	/**
	 * Application path
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Configure
	 * 
	 */
	public function configure() 
	{
		$config = CmsApplicationConfiguration::getInstance();
		$config->addConfiguration($this);
	}
	
}
