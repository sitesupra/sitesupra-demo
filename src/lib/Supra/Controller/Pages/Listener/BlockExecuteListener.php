<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;

/**
 * Description of BlockExecuteListener
 *
 * @author aleksey
 */
class BlockExecuteListener implements EventSubscriber
{
	
	
	private $statisticsData;
	
	/**
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			BlockEvents::blockStartExecuteEvent,
			BlockEvents::blockEndExecuteEvent,
			PageController::EVENT_POST_PREPARE_CONTENT,
			//SqlEvents::startQuery,
			//SqlEvents::endQuery,
		);
	}
	
	
	public function blockStartExecuteEvent(BlockEventsArgs $eventArgs){
			
		$this->statisticsData[] = $eventArgs->blockClass .' - start;';
		
	}
	
	
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs){
		$this->statisticsData[] = $eventArgs->blockClass .
				' - end; execution time: ' .
				"{$eventArgs->duration} microseconds.";
		
	}
	
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs){
		/**
		 * @todo do something on all page end execution
		 */
		
		$a = 1+1;
	}
	
}
