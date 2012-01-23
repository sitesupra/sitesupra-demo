<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\SqlEvents;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Event\SqlEventsArgs;
use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;

/**
 * BlockExecuteListener
 */
class BlockExecuteListener implements EventSubscriber
{

	/**
	 * Statistics output array
	 * @var array
	 */
	private $statisticsData;

	/**
	 * Block queries counter
	 * @var integer
	 */
	private $queriesCounter = 0;

	/**
	 * Block queries execution time counter
	 * @var float
	 */
	private $queriesTimeCounter = 0;

	/**
	 * Show is block on run now
	 * @var boolean
	 */
	private $runBlockFlag = false;

	/**
	 * Return subscribed events list
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			BlockEvents::blockStartExecuteEvent,
			BlockEvents::blockEndExecuteEvent,
			PageController::EVENT_POST_PREPARE_CONTENT,
			SqlEvents::startQuery,
			SqlEvents::stopQuery,
		);
	}

	/**
	 * Start of the block execution event handler
	 * @param BlockEventsArgs $eventArgs 
	 */
	public function blockStartExecuteEvent(BlockEventsArgs $eventArgs)
	{
		$this->queriesCounter = 0;
		$this->queriesTimeCounter = 0;
		$this->runBlockFlag = true;
		//$this->statisticsData[] = $eventArgs->blockClass .' - start;';		
	}

	/**
	 * End of the block execution event handler
	 * @param BlockEventsArgs $eventArgs 
	 */
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs)
	{
		$this->runBlockFlag = false;

		$time = round($eventArgs->duration * 1000);

		$message = $eventArgs->blockClass .
				' - executed in ' .
				"{$time} milliseconds;";

		if ($this->queriesCounter) {

			$time = round($this->queriesTimeCounter * 1000);

			$message .= " executed {$this->queriesCounter} " .
					"queries in {$time} milliseconds.";
		}

		$this->statisticsData[] = $message;
	}

	/**
	 * Start query execution event handler
	 * @param SqlEventsArgs $eventArgs 
	 */
	public function startQuery(SqlEventsArgs $eventArgs)
	{
		
	}

	/**
	 * Stop query execution event handler
	 * @param SqlEventsArgs $eventArgs
	 * @return void 
	 */
	public function stopQuery(SqlEventsArgs $eventArgs)
	{

		if ( ! $this->runBlockFlag) {
			return;
		}

		$time = $eventArgs->stopTime - $eventArgs->startTime;
		$this->queriesTimeCounter += $time;
		$this->queriesCounter ++;
	}

	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{


		$response = new \Supra\Response\TwigResponse($this);
		$response->assign('debugData', $this->statisticsData);
		$response->outputTemplate('block_execute_listener.js.twig');

		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $response);
	}

}
