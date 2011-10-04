<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Configuration\ConfigurationInterface;

/**
 * Configuration for page applications, works as 
 */
class PageApplicationConfiguration implements ConfigurationInterface
{
	public $id;
	
	public $className;
	
	public function configure()
	{
		PageApplicationCollection::getInstance()
				->addConfiguration($this);
	}
}
