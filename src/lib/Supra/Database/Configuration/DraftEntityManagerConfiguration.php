<?php

namespace Supra\Database\Configuration;

use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener;
use Supra\NestedSet\Listener\NestedSetListener;

/**
 * 
 */
class DraftEntityManagerConfiguration extends EntityManagerConfiguration
{
	public function configure()
	{
		$this->name = PageController::SCHEMA_DRAFT;
		$this->objectRepositoryBindings[] = 'Supra\Cms';
		
		parent::configure();
	}
	
	protected function configureEventManager(EventManager $eventManager)
	{
		parent::configureEventManager($eventManager);
		
		$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
		
		// Nested set entities (pages and files) depends on this listener
		$eventManager->addEventSubscriber(new NestedSetListener());
		
		$eventManager->addEventSubscriber(new Listener\ImageSizeCreatorListener());
		$eventManager->addEventSubscriber(new Listener\TableDraftSuffixAppender());

		// NB! ORDER DOES MATTER!
		// Revision id must be filled before entity goes to audit listener
		// Manage entity revision values
		$eventManager->addEventSubscriber(new Listener\EntityRevisionSetterListener());
		// Audit entity changes in Draft schema
		$eventManager->addEventSubscriber(new Listener\EntityAuditListener());
		
		// Now public entity manager will receive the page move events as well
		$eventManager->addEventSubscriber(new Listener\PageMovePublicEventPush());
	}

}
