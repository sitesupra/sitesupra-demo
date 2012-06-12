<?php

namespace Project\AjaxContentBlock;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Ajax content block
 */
class AjaxContentBlock extends BlockController
{
	
	public function doExecute()
	{
		$response = $this->getResponse();
		// Local file is used
		$response->outputTemplate('index.html.twig');
	}
	
	// This stuff to be sent to some extension...
	public function doPrepare()
	{
		$eventManager = \Supra\ObjectRepository\ObjectRepository::getEventManager();
		$eventManager->listen(\Supra\Controller\Pages\Event\BlockEvents::blockStartExecuteEvent, array($this, 'blockStartExecuteEvent'));
		$eventManager->listen(\Supra\Controller\Pages\Event\BlockEvents::blockEndExecuteEvent, array($this, 'blockEndExecuteEvent'));
		
		parent::doPrepare();
	}
	
	public function blockStartExecuteEvent(\Supra\Controller\Pages\Event\BlockEventsArgs $eventArgs)
	{
		// TODO: should output only
		if ($eventArgs->blockController === $this && $eventArgs->actionType == \Supra\Controller\Pages\Listener\BlockExecuteListener::ACTION_CONTROLLER_EXECUTE && ! $eventArgs->blockRequest) {
			$eventArgs->blockController->getResponse()->output('<div data-attach="$.app.AjaxContent" data-id="' . $eventArgs->block->getId() . '" data-url="?block_id=' . $eventArgs->block->getId() . '">');
		}
	}
	public function blockEndExecuteEvent(\Supra\Controller\Pages\Event\BlockEventsArgs $eventArgs)
	{
		if ($eventArgs->blockController === $this && $eventArgs->actionType == \Supra\Controller\Pages\Listener\BlockExecuteListener::ACTION_CONTROLLER_EXECUTE && ! $eventArgs->blockRequest) {
			$eventArgs->blockController->getResponse()->output('</div>');
		}
	}
}
