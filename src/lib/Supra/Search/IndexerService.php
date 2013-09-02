<?php

namespace Supra\Search;

class IndexerService
{
	/**
	 * Takes all FRESH items from $queue and adds them to Solr.
	 * @param IndexerQueue $queue 
	 */
	public function processQueue(IndexerQueue $queue)
	{
//		$indexedQueueItems = array();

		$documentCount = 0;

		while ($queue->getItemCountForStatus(IndexerQueueItemStatus::FRESH) !== 0) {

			$queueItem = $queue->getNextItemForIndexing();

			$documentCount = $documentCount + $this->processItem($queueItem);
//			$indexedQueueItems[] = $queueItem;

			$queue->store($queueItem);
		}

		//foreach($indexedQueueItems as $indexedQueueItem) {
		//	
		//	$indexedQueueItem->setStatus(IndexerQueueItemStatus::FRESH);
		//	$queue->store($indexedQueueItem);
		//}

		return $documentCount;
	}
	
	/**
	 * Call adapter functions through this class
	 * @param type $method
	 * @param type $arguments
	 * @return boolean
	 */
	public function __call( $method, $arguments = array() )
	{
		if ( function_exists( $this, $method ) )
		{
			return call_user_func_array( array( $this, $method ), $arguments );
		}
		else
		{
			try {
				return call_user_func_array( array( IndexerService::getAdapter(), $method ), $arguments );
			}
			catch( Exception\BadSchemaException $e )
			{
				\Log::error($e->getMessage());
				return FALSE;
			}
		}
	}

	/**
	 * @var Singelton
	 */
	protected static $adapter = array();
	
	/**
	 * @return \Supra\Search\{Adapter}\IndexerService
	 */
	public static function getAdapter( $adapter = NULL )
	{
		$adapter = ( $adapter == NULL ) ? SEARCH_SERVICE_ADAPTER : $adapter;
		$adapterClass = '\\Supra\\Search\\' . $adapter . '\\IndexerService';
		
		if ( !isset( IndexerService::$adapter[$adapter] ) )
		{
			try {
				IndexerService::$adapter[$adapter] = new $adapterClass();
			}
			catch ( Exception\BadSchemaException $e )
			{
				\Log::error($e->getMessage());
				throw $e;
			}
		}
		
		return IndexerService::$adapter[$adapter];
	}
	
}
