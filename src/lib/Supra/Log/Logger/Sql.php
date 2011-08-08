<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Supra\Log\Logger;

/**
 * Sql class
 */
class Sql implements SQLLogger
{
	/**
	 * @var string
	 */
	private $sql;
	
	/**
	 * @var array
	 */
	private $params;
	
	/**
	 * @var array
	 */
	private $types;
	
	/**
	 * @var float
	 */
	private $start;

	/**
	 * {@inheritdoc}
	 */
	public function startQuery($sql, array $params = null, array $types = null)
	{
		$this->sql = $sql;
		$this->params = $params;
		$this->types = $types;
		$this->start = microtime(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function stopQuery()
	{
		$message = "Query\n{$this->sql}\n/* has been run";
		if (count($this->params) > 0) {
			//FIXME: DateTime objects raises exception
//			$message .= " with parameters [" . implode(', ', $this->params) . "]";
		}
		
		$executionMs = microtime(true) - $this->start;
		$executionMs = round(1000000 * $executionMs);
		$message .= ", execution time {$executionMs}ms*/";
		
		Logger::debug($message);
	}

}