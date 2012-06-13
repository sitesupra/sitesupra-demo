<?php

namespace SupraX;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Listener\BlockExecuteListener;

/**
 * HtmlInjection
 */
class HtmlInjection implements ConfigurationInterface
{
	const CN = __CLASS__;
	
	public $id;
	
	public $version = '1.0';
	
	public $snippet;
	
	public $files = array();
	
	public $depends = array();
	
	public function configure()
	{
		// Save inside object repository
		ObjectRepository::setDefaultObject($this, self::CN);
	}
	
	/**
	 * TODO: this must be called by block controller on initiation, configured as dependency onder configuration
	 * @param BlockController $blockController
	 */
	public function __invoke(BlockController $blockController)
	{
		$eventManager = ObjectRepository::getEventManager();
		$eventManager->listen(array(BlockEvents::blockStartExecuteEvent), array($this, 'blockStartExecuteEvent'), $blockController);
	}
	
	public function blockStartExecuteEvent(BlockEventsArgs $eventArgs)
	{
		if ($eventArgs->actionType == BlockExecuteListener::ACTION_CONTROLLER_EXECUTE) {
			
			foreach ($this->depends as $dependency) {
				$dependency = ObjectRepository::getObject($dependency, self::CN);
				
				//TODO: Check for dependency loops
				$dependency->blockStartExecuteEvent($eventArgs);
			}
			
			$context = $eventArgs->blockController
					->getResponse()
					->getContext();
			
			foreach ($this->files as $file) {
				//TODO: check if not included already
				$context->addJsToLayoutSnippet($this->snippet, $file);
			}
		}
	}
}
