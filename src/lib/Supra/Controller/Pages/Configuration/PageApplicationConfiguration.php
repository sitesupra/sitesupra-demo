<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Configuration\ConfigurationInterface;

/**
 * Configuration for page applications, works as 
 */
class PageApplicationConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $id;
	
	/**
	 * @var string
	 */
	public $className;
	
	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $icon;
	
	public function configure()
	{
		PageApplicationCollection::getInstance()
				->addConfiguration($this);
	}
}
