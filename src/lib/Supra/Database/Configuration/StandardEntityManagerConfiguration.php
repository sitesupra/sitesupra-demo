<?php

namespace Supra\Database\Configuration;

use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\FileStorage\Listener\FilePathGenerator;

/**
 * 
 */
class StandardEntityManagerConfiguration extends EntityManagerConfiguration
{
	protected function configureEventManager(EventManager $eventManager)
	{
		parent::configureEventManager($eventManager);
		
		$eventManager->addEventSubscriber(new PagePathGenerator());
		
		// Nested set entities (pages and files) depends on this listener
		$eventManager->addEventSubscriber(new NestedSetListener());
		
		$eventManager->addEventSubscriber(new FilePathGenerator());
	}

}
