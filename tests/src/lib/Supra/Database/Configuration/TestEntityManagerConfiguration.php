<?php

namespace Supra\Tests\Database\Configuration;

use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener;
use Doctrine\ORM\Events;
use Supra\Tests\Search\DiscriminatorAppender;

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
		
		$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
		$eventManager->addEventSubscriber(new Listener\ImageSizeCreatorListener());
		
		$eventManager->addEventListener(array(Events::loadClassMetadata), new DiscriminatorAppender());
	}

}
