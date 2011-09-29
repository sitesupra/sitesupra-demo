<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\Application\PageApplicationCollection;

/**
 * Configuration for page applications, works as 
 */
class PageApplicationConfiguration
{
	public $id;
	
	public $className;
	
	public function configure()
	{
		PageApplicationCollection::getInstance()
				->addConfiguration($this);
	}
}
