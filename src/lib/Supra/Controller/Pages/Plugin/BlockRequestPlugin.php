<?php

namespace Supra\Controller\Pages\Plugin;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Listener\BlockExecuteListener;
use Supra\Controller\Pages\Plugin\BlockControllerPlugin;
use Supra\Controller\Pages\BlockController;

/**
 * Binds the events
 */
class BlockRequestPlugin extends BlockControllerPlugin
{
	public function bind(BlockController $blockController)
	{
		$eventManager = ObjectRepository::getEventManager();
		$eventManager->listen(BlockEvents::blockStartExecuteEvent, array($this, 'blockStartExecuteEvent'), $blockController);
		$eventManager->listen(BlockEvents::blockEndExecuteEvent, array($this, 'blockEndExecuteEvent'), $blockController);
		
		// Dependency
		$dependency = ObjectRepository::getObject('assets.js.app.ajaxcontent', BlockControllerPlugin::CN);
		$dependency->bind($blockController);
	}
	
	public function blockStartExecuteEvent(BlockEventsArgs $eventArgs)
	{
		if ($eventArgs->actionType == BlockExecuteListener::ACTION_CONTROLLER_EXECUTE && ! $eventArgs->blockRequest) {
			$eventArgs->blockController->getResponse()->output(
					'<div data-attach="$.app.AjaxContent" data-id="'
					. $eventArgs->block->getId()
					. '" data-url="?block_id='
					. $eventArgs->block->getId() . '">');
			
			$blockController = $eventArgs->blockController;
//			$response = $blockController
		}
	}
	
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs)
	{
		if ($eventArgs->actionType == BlockExecuteListener::ACTION_CONTROLLER_EXECUTE && ! $eventArgs->blockRequest) {
			$eventArgs->blockController->getResponse()->output('</div>');
		}
	}

}
