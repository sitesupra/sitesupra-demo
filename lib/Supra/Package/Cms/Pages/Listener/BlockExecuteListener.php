<?php

namespace Supra\Package\Cms\Pages\Listener;

use Supra\Controller\Pages;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\SqlEvents;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Event\SqlEventsArgs;
use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\Event\Exception\LogicException;
use Supra\Response;

/**
 * BlockExecuteListener
 */
class BlockExecuteListener implements EventSubscriber
{
	const ACTION_CACHE_SEARCH = 'search cache';
	const ACTION_CONTROLLER_SEARCH = 'find controller';
	const ACTION_CONTROLLER_PREPARE = 'prepare controller';
	const ACTION_DEPENDENT_CACHE_SEARCH = 'search context dependent cache';
	const ACTION_CONTROLLER_EXECUTE = 'execute controller';
	const ACTION_RESPONSE_COLLECT = 'collect responses';

	const CACHE_TYPE_FULL = 'full';
	const CACHE_TYPE_CONTEXT = 'context dependent';

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
	 * Contains records about blocks, that were loaded from blocks cache 
	 * @var array
	 */
	private $blockCacheTypes = array();

	/**
	 * Contains exception object of failed blocks.
	 * @var array
	 */
	private $blockExceptions = array();

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
	 *
	 * @param BlockEventsArgs $eventArgs
	 * @return Response\ResponseContextLocalProxy
	 */
	private function getLocalResponseContext(BlockEventsArgs $eventArgs)
	{
		$controller = $eventArgs->blockController;

		if ( ! $controller instanceof Pages\BlockController) {
			return;
		}

		$response = $controller->getResponse();

		if ( ! $response instanceof Response\HttpResponse) {
			return;
		}

		$context = $response->getContext();

		if ( ! $context instanceof Response\ResponseContextLocalProxy) {
			return;
		}

		$context = $context->getLocalContext();

		return $context;
	}

	/**
	 * End of the block execution event handler
	 * @param BlockEventsArgs $eventArgs 
	 */
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs)
	{
		$this->runBlockFlag = false;

		$blockOid = $eventArgs->block->getId();
		$context = $this->getLocalResponseContext($eventArgs);
		$actionType = $eventArgs->actionType;

		// Load data from the cache if cached
		if (isset($this->blockCacheTypes[$blockOid]) && ! empty($context)) {

			$originalData = $context->getValue(__CLASS__ . '_' . $blockOid);

			if (isset($originalData[$actionType])) {

				// Mark as cache
				$stats = $originalData[$actionType];
				$stats[3] = 1;

				$this->statisticsData[$blockOid][$actionType] = $stats;

				return;
			}
		}

		$this->statisticsData[$blockOid][$actionType] = array_fill(0, 4, null);
		$stats = &$this->statisticsData[$blockOid][$actionType];

		$this->blockClassNames[$blockOid] = $eventArgs->block->getComponentClass();

		$time = round($eventArgs->duration * 1000);

		$stats[0] = $time;

		if ($this->queriesCounter) {
			$time = round($this->queriesTimeCounter * 1000);

			$stats[1] = $this->queriesCounter;
			$stats[2] = $time;
		}

		// passing info about block cache
		if ($eventArgs->cached && ! isset($this->blockCacheTypes[$blockOid])) {
			if ($actionType == self::ACTION_CACHE_SEARCH) {
				$this->blockCacheTypes[$blockOid] = self::CACHE_TYPE_FULL;
			} elseif ($actionType == self::ACTION_DEPENDENT_CACHE_SEARCH) {
				$this->blockCacheTypes[$blockOid] = self::CACHE_TYPE_CONTEXT;
			} else {
				$this->blockCacheTypes[$blockOid] = 'unknown cache';
			}
		}

		// Save stats cache after execution
		if ($actionType == self::ACTION_CONTROLLER_EXECUTE && ! empty($context)) {
			$context->setValue(__CLASS__ . '_' . $blockOid, $this->statisticsData[$blockOid]);
		}

		if ( ! empty($eventArgs->exception)) {
			$this->blockExceptions[$blockOid] = $eventArgs->exception;
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

			$blockStats = array(
				'blockId' => $oid,
				'totals' => null,
				'actions' => array(),
				'cache' => null,
				'actions_cache' => array(),
			);
			$overallTime =
					$totalQueries =
					$totalQueryTime = 0;

			$overallTimeCached =
					$totalQueriesCached =
					$totalQueryTimeCached = 0;

			foreach ($stats as $actionType => $singleActionStats) {

				$actionCategory = null;

				if ( ! empty($singleActionStats[3])) {
					$overallTimeCached += $singleActionStats[0];
					$totalQueriesCached += $singleActionStats[1];
					$totalQueryTimeCached += $singleActionStats[2];

					$actionCategory = 'actions_cache';
				} else {
					$overallTime += $singleActionStats[0];
					$totalQueries += $singleActionStats[1];
					$totalQueryTime += $singleActionStats[2];

					$actionCategory = 'actions';
				}

				if ($singleActionStats[0] < 2) {
					continue;
				}

				array_unshift($singleActionStats, $actionType);

				$message = vsprintf('    %-46s %4dms', array_slice($singleActionStats, 0, 2));

				if ( ! empty($singleActionStats[2])) {
					$message .= vsprintf(' %3d queries (%4dms)', array_slice($singleActionStats, 2, 2));
				}

				$blockStats[$actionCategory][] = $message;
			}

			if ($totalQueries > 0) {
				$blockStats['totals'] = vsprintf('%-50s %4dms %3d queries (%4dms)    [%20s]', array($name, $overallTime, $totalQueries, $totalQueryTime, $oid));
			} else {
				$blockStats['totals'] = vsprintf('%-50s %4dms                         [%20s]', array($name, $overallTime, $oid));
			}

			if ( ! empty($this->blockCacheTypes[$oid])) {
				$cacheName = $this->blockCacheTypes[$oid];

				$message = vsprintf('  %-48s %4dms', array('Cached stages', $overallTimeCached));

				if ($totalQueriesCached > 0) {
					$message .= vsprintf(' %3d queries (%4dms)', array($totalQueriesCached, $totalQueryTimeCached));
				}

				$blockStats['cache'] = $message;
			}

			if ( ! empty($this->blockExceptions[$oid])) {

				$exception = $this->blockExceptions[$oid];

				$blockStats['exception'] = $exception;
			}

			$responseData[] = $blockStats;
		}

		$response = new Response\TwigResponse($this);
		$response->assign('debugData', $responseData);
		$response->outputTemplate('block_execute_listener.js.twig');

		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $response);
	}

}
