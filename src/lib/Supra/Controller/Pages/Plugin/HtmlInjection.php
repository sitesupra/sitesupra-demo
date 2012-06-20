<?php

namespace Supra\Controller\Pages\Plugin;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Listener\BlockExecuteListener;
use Supra\Controller\Pages\BlockController;

/**
 * HtmlInjection block controller plugin
 */
class HtmlInjection extends BlockControllerPlugin
{
	/**
	 * @var array
	 */
	public $html = array();
	
	/**
	 * @var array
	 */
	public $depends = array();
	
	public function bind(BlockController $blockController)
	{
		foreach ($this->depends as $resourceId) {
			$dependance = ObjectRepository::getObject($resourceId, BlockControllerPlugin::CN, true);
			$dependance->bind($blockController);
		}
		
		$eventManager = ObjectRepository::getEventManager();
		$eventManager->listen(BlockEvents::blockStartExecuteEvent, array($this, 'blockStartExecuteEvent'), $blockController);
	}
	
	public function blockStartExecuteEvent(BlockEventsArgs $eventArgs)
	{
		if ($eventArgs->actionType === BlockExecuteListener::ACTION_CONTROLLER_EXECUTE) {
			$blockController = $eventArgs->blockController;

			$responseContext = $blockController->getResponse()
					->getContext();
			
			// Can check for single output only if ID is set
			if ( ! empty($this->id)) {
				$contextFlagName = __CLASS__ . '#' . $this->id;
				if ($responseContext->offsetExists($contextFlagName)) {
					return;
				}
				$responseContext->offsetSet($contextFlagName, true);
			}

			foreach ($this->html as $key => $snippet) {
				$responseContext->addToLayoutSnippet($key, $snippet);
			}
			
		}
	}
}
