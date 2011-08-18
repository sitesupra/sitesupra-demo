<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger as SQLLoggerInterface;
use Supra\Log\Writer\WriterAbstraction;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Sql class
 */
class SqlLogger implements SQLLoggerInterface
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
		$subject = "Query\n{$this->sql}\n/* has been run";
		if (count($this->params) > 0) {
			//FIXME: DateTime objects raises exception
//			$subject .= " with parameters [" . implode(', ', $this->params) . "]";
		}
		
		$executionMs = microtime(true) - $this->start;
		$executionMs = round(1000000 * $executionMs);
		$subject .= ", execution time {$executionMs}ms*/";
		
		$log = ObjectRepository::getLogger($this);
		
		// Let's find first caller offset not inside the Doctrine package
		$offset = 1;
		$trace = debug_backtrace(false);
		array_shift($trace);
		
		foreach ($trace as $traceElement) {
			$class = null;
			if (isset($traceElement['class'])) {
				$class = $traceElement['class'];
			}
			if (strpos($class, 'Doctrine\\') !== 0) {
				break;
			}
			$offset++;
		}
		
		$log->increaseBacktraceOffset($offset);
		$log->debug($subject);
	}

}