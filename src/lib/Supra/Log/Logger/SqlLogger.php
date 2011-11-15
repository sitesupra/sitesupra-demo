<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger as SQLLoggerInterface;
use Supra\Log\Writer\WriterAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\LogEvent;

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
	 * @param string $subject
	 */
	private function log($subject, $level = LogEvent::DEBUG)
	{
		$log = ObjectRepository::getLogger($this);
		
		// Let's find first caller offset not inside the Doctrine package
		$offset = 0;
		$trace = debug_backtrace(false);
		array_shift($trace);
		
		foreach ($trace as $traceElement) {
			$class = null;
			if (isset($traceElement['class'])) {
				$class = $traceElement['class'];
			}
			if ($class != __CLASS__ && strpos($class, 'Doctrine\\') !== 0) {
				break;
			}
			
//			$log->debug("$class:{$traceElement['line']}");
			
			$offset++;
		}
		
		$log->increaseBacktraceOffset($offset);
		$log->__call($level, array($subject));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function startQuery($sql, array $params = null, array $types = null)
	{
		$this->sql = $sql;
		$this->params = $params;
		$this->types = $types;
		$this->start = microtime(true);
		
		// Fix DateTime object logging
		foreach ($this->params as $key => &$param) {
			if ($param instanceof \DateTime) {
				$this->params[$key] = $param->format('c');
			}
			if ($param instanceof \Supra\Uri\Path) {
				$this->params[$key] = $param->getFullPath();
			}
			if (is_null($param)) {
				$param = 'NULL';
			}
			if (is_array($param)) {
				$param = 'ARRAY[' . implode('; ', $param) . ']';
			}
			
			$length = mb_strlen($param);
			
			if ($length > 70) {
				$size = ($length - 50) . " chars";
				$param = mb_substr($param, 0, 50) . "...<$size>..." . mb_substr($param, $length - 10);
			}
		}
		unset($param);
		
		$level = $this->getLogLevel();
		
		// Enable when you need to know the parameters
		$this->log($this->sql . "\n"
				. ($this->params ? "(" . implode('; ', $this->params) . ")\n" : ""), 
				$level);
		
//		$subject = "Query\n{$this->sql}\n";
//		$this->log($subject, $level);
	}

	/**
	 * {@inheritdoc}
	 */
	public function stopQuery()
	{
		// Enable if you need to know the execution time
		return;
		
		$subject = "Query\n{$this->sql}\n/* has been run";
		if (count($this->params) > 0) {
			//FIXME: DateTime objects raises exception
//			$subject .= " with parameters [" . implode(', ', $this->params) . "]";
		}
		
		$executionMs = microtime(true) - $this->start;
		$executionMs = round(1000000 * $executionMs);
		$subject .= ", execution time {$executionMs}ms*/";
		
		$level = $this->getLogLevel();
		
		$this->log($subject, $level);
	}
	
	/**
	 * @return string
	 */
	protected function getLogLevel()
	{
		// Log selects with DEBUG level, other with INFO
		$sql = ltrim($this->sql);
		if (stripos($sql, 'SELECT') !== 0) {
			return LogEvent::INFO;
		}
		
		return LogEvent::DEBUG;
	}
}