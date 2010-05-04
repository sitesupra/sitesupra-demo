<?php

namespace Supra\Log;

/**
 * Supra log event object
 */
class Event
{
	/**
	 * Log data
	 * @var mixed
	 */
	public $data;
	
	/**
	 * Timestamp
	 * @var int
	 */
	public $timestamp;
	
	/**
	 * Microtime in miliseconds
	 * @var int
	 */
	public $microtime;
	
	/**
	 * Log level
	 * @var string
	 */
	public $level;
	
	/**
	 * File name
	 * @var string
	 */
	public $file;
	
	/**
	 * Code line
	 * @var int
	 */
	public $line;
	
	/**
	 * Logger name
	 * @var string
	 */
	public $logger;
	
	/**
	 * From log data generated message
	 * @var string
	 */
	protected $message;
	
	/**
	 * Log level priority
	 * @var int
	 */
	protected $levelPriority;
	
	/**
	 * Other event parameters
	 * @var array
	 */
	protected $params;
	
	/**
	 * Log event constructor
	 * @param array $data
	 * @param string $level
	 * @param string $file
	 * @param int $line
	 */
	function __construct($data, $level, $file, $line, $logger, array $params = array())
	{
		$this->data = $data;
		$this->level = $level;
		$this->file = (string)$file;
		$this->line = (int)$line;
		$this->timestamp = time();
		
		// get microtime - work with string to work-around the float precision issue
		$this->microtime = (string)microtime(true);
		$pointPos = strpos($this->microtime, '.');
		if ($pointPos !== false) {
			$this->microtime = str_replace('.', '', $this->microtime);
			$zeroCount = 3 - strlen($this->microtime) + $pointPos;
		} else {
			$zeroCount = 3;
		}
		if ($zeroCount > 0) {
			$this->microtime .= str_repeat('0', $zeroCount);
		} elseif ($zeroCount < 0) {
			$this->microtime = substr($this->microtime, 0, $zeroCount);
		}
		
		$this->logger = $logger;
		$this->params = (array)$params;
	}
	
	/**
	 * Get log event parameter array or array value
	 * @param string $name
	 * @return mixed
	 */
	function getParams($name = null)
	{
		if (is_null($name)) {
			return $this->params;
		} elseif (array_key_exists($name, $this->params)) {
			return $this->params[$name];
		} else {
			return null;
		}
	}
	
	/**
	 * Log message function
	 *
	 * @return string
	 */
	function getMessage()
	{
		if (is_null($this->message)) {
			$this->message = '';
			foreach ($this->data as &$item) {
				$this->message .= self::formatObject($item);
			}
		}
		return $this->message;
	}
	
	/**
	 * Log level priority
	 * @return int
	 */
	function getLevelPriority()
	{
		if (is_null($this->levelPriority)) {
			if (isset(Logger::$levels[$this->level])) {
				$this->levelPriority = Logger::$levels[$this->level];
			} else {
				throw new Exception(__CLASS__ . ': level not recognized - ' . $this->level);
			}
		}
		return $this->levelPriority;
	}
	
	/**
	 * Format object for readable representation
	 */
	private static function formatObject(&$obj)
	{
		return Logger::formatObject($obj);
	}
	
	/**
	 * Object cast to array
	 *
	 * @return array
	 */
	function toArray()
	{
		return array(
			'timestamp' => $this->timestamp,
			'microtime' => $this->microtime,
			'message' => $this->getMessage(),
			'level' => $this->level,
			'levelPriority' => $this->getLevelPriority(),
			'file' => $this->file,
			'line' => $this->line,
			'logger' => $this->logger,
			'thread' => getmypid(),
		) + $this->params;
	}
}