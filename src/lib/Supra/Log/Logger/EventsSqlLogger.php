<?php

namespace Supra\Log\Logger;

use Supra\Controller\Pages\Event\SqlEvents;
use Supra\Controller\Pages\Event\SqlEventsArgs;
use Doctrine\DBAL\Logging\SQLLogger as SQLLoggerInterface;
use Supra\ObjectRepository\ObjectRepository;

class EventsSqlLogger implements SQLLoggerInterface
{
	
	private $eventArgs;

	/**
	 * Handle start of query execution
	 * @param string $sql
	 * @param array $params
	 * @param array $types 
	 */
    public function startQuery($sql, array $params = null, array $types = null)
	{
		$eventArgs = new SqlEventsArgs();
		$eventArgs->sql = $sql;
		$eventArgs->params = $params;
		$eventArgs->types = $types;
	
		$eventArgs->startTime = microtime(true);
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(SqlEvents::startQuery, $eventArgs);
		
		$this->eventArgs = $eventArgs;
	}
	

    /**
     * Mark the last started query as stopped.
     * @return void
     */
    public function stopQuery()
	{
		$eventArgs = $this->eventArgs;
		$eventArgs->stopTime = microtime(true);
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(SqlEvents::stopQuery, $eventArgs);		
	}
}
