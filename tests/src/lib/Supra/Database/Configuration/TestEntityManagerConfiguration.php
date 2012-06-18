<?php

namespace Supra\Tests\Database\Configuration;

use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener;
use Doctrine\ORM\Events;
use Supra\Tests\Search\DiscriminatorAppender;
use Supra\NestedSet\Listener\NestedSetListener;

/**
 * Entity Manager Configuration for test connection
 */
class TestEntityManagerConfiguration extends \Supra\Database\Configuration\EntityManagerConfiguration
{
	protected function configureProxy(Configuration $config)
	{
		// Proxy configuration
		$config->setProxyDir(SUPRA_TESTS_LIBRARY_PATH . 'Supra/Proxy/');
		$config->setProxyNamespace('Supra\Tests\Proxy');
		$config->setAutoGenerateProxyClasses(false);
	}
	
	protected function configureEventManager(EventManager $eventManager)
	{
		parent::configureEventManager($eventManager);
		
		// Nested set entities (pages and files) depends on this listener
		$eventManager->addEventSubscriber(new NestedSetListener());
		
		$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
		$eventManager->addEventSubscriber(new Listener\ImageSizeCreatorListener());
		
		$eventManager->addEventListener(array(Events::loadClassMetadata), new DiscriminatorAppender());
	}

}
