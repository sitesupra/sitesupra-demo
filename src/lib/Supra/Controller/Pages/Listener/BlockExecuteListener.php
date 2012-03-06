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
use Supra\Event\Exception\LogicException;

/**
 * BlockExecuteListener
 */
class BlockExecuteListener implements EventSubscriber
{
	
	const ACTION_CACHE_SEARCH = 'search cache';
	const ACTION_CONTROLLER_SEARCH = 'find controller';
	const ACTION_CONTROLLER_PREPARE = 'prepare controller';
	const ACTION_DEPENDANT_CACHE_SEARCH = 'search context dependent cache';
	const ACTION_CONTROLLER_EXECUTE = 'execute controller';
	const ACTION_RESPONSE_COLLECT = 'collect responses';
	
	const CACHE_TYPE_FULL = 'full';
	const CACHE_TYPE_CONTEXT = 'context dependant';
	
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
	 * Storage for executed block class names
	 * @var array
	 */
	private $blockClassNames = array();
	
	/**
	 * Contais records about blocks, that were loaded from blocks cache 
	 * @var array
	 */
	private $blockCacheTypes = array();

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
//			SqlEvents::startQuery,
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
	}

	/**
	 * End of the block execution event handler
	 * @param BlockEventsArgs $eventArgs 
	 */
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs)
	{
		$this->runBlockFlag = false;
		
		$blockOid = spl_object_hash($eventArgs->block);
		
		$this->statisticsData[$blockOid][$eventArgs->actionType] = array();
		$stats = &$this->statisticsData[$blockOid][$eventArgs->actionType];

		$this->blockClassNames[$blockOid] = $eventArgs->block->getComponentClass();
		
		$time = round($eventArgs->duration * 1000);

		$stats[] = $time;

		if ($this->queriesCounter) {
			$time = round($this->queriesTimeCounter * 1000);
			
			$stats[] = $this->queriesCounter;
			$stats[] = $time;
			
		}
		
		// passing info about block cache
		if (isset($eventArgs->blockCacheInfo) && ! isset($this->blockCacheTypes[$blockOid])) {
			$this->blockCacheTypes[$blockOid] = $eventArgs->blockCacheInfo;
		}
	}

//	/**
//	 * Start query execution event handler
//	 * @param SqlEventsArgs $eventArgs 
//	 */
//	public function startQuery(SqlEventsArgs $eventArgs)
//	{
//		
//	}

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
		if (empty($this->statisticsData)) {
			return;
		}
		
		$responseData = array();
		
		foreach ($this->statisticsData as $oid => $stats) {
			
			$name = $this->blockClassNames[$oid];
			
			$blockStats = array();
			$overallTime = 
				$totalQueries = 
				$totalQueryTime = 0;
			
			foreach ($stats as $actionType => $singleActionStats) {
				
				$overallTime += $singleActionStats[0];
				
				if ($singleActionStats[0] < 2) {
					continue;
				}
				
				if (isset($singleActionStats[1])) {
					$totalQueries += $singleActionStats[1];
					$totalQueryTime += $singleActionStats[2];
					
					array_unshift($singleActionStats, $actionType);
					$blockStats['actions'][] = vsprintf('     %-45s %4dms %3d queries (%4dms)', $singleActionStats);
					
				} else {
					$blockStats['actions'][] = vsprintf('     %-45s %4dms', array($actionType, $singleActionStats[0]));
				}
			}
			
			if ($totalQueries > 0) {
				$blockStats['totals'] = vsprintf('%-50s %4dms %3d queries (%4dms)', array($name, $overallTime, $totalQueries, $totalQueryTime));
			} else {
				$blockStats['totals'] = vsprintf('%-50s %4dms', array($name, $overallTime));
			}
			
			if ( ! empty($this->blockCacheTypes[$oid])) {
				
				$cacheType = $this->blockCacheTypes[$oid];
				
				$blockStats['actions'][] = vsprintf('     %-48s %s', array('cache used', $this->blockCacheTypes[$oid]));
			}
			
			$responseData[] = $blockStats;
		}
		
		$response = new \Supra\Response\TwigResponse($this);
		$response->assign('debugData', $responseData);
		$response->outputTemplate('block_execute_listener.js.twig');

		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $response);
	}

}
