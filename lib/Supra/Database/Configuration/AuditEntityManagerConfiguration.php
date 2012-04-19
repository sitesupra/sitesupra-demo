<?php

namespace Supra\Database\Configuration;

use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener;

/**
 * 
 */
class AuditEntityManagerConfiguration extends EntityManagerConfiguration
{
	public function configure()
	{
		$this->name = PageController::SCHEMA_AUDIT;
		$this->objectRepositoryBindings[] = 'Supra\Cms\Abstraction\Audit';
		
		parent::configure();
	}
	
	protected function configureEventManager(EventManager $eventManager)
	{
		parent::configureEventManager($eventManager);
		
		$eventManager->addEventSubscriber(new Listener\AuditManagerListener());
		$eventManager->addEventSubscriber(new Listener\AuditCreateSchemaListener());
	}

}
