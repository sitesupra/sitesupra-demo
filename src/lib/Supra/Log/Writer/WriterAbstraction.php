<?php

namespace Supra\Log\Writer;

require_once SUPRA_LIBRARY_PATH . 'Supra/Log/Event.php';
require_once SUPRA_LIBRARY_PATH . 'Supra/Log/Formatter/Log4j.php';

use Supra\Log\Filter;
use Supra\Log\Formatter;
use Supra\Log\Logger;
use Supra\Log\Event;

/**
 * Abstract for log writer
 */
abstract class WriterAbstraction implements WriterInterface
{
	
	/**
	 * Formatter instance
	 *
	 * @var Formatter\FormatterInterface
	 */
	protected $formatter;
	
	/**
	 * Filter instance
	 *
	 * @var Filter\FilterInterface[]
	 */
	protected $filters = array();
	
	/**
	 * Configuration
	 *
	 * @var array
	 */
	protected $parameters;
	
	/**
	 * Logger name
	 *
	 * @var string
	 */
	protected $name;
	
	/**
	 * If the writer method is running now. Used to workaround recursions
	 *
	 * @var bool
	 */
	private $writing = false;
	
	/**
	 * Default configuration
	 *
	 * @var array
	 */
	public static $defaultParameters = array();

	/**
	 * Default formatter class
	 * @var string
	 */
	public static $defaultFormatter = 'Supra\Log\Formatter\Simple';

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array();
	
	/**
	 * Log writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		$this->parameters = $parameters + static::$defaultParameters;
	}

	/**
	 * Get log formatter
	 * @return Formatter\FormatterInterface
	 */
	public function getFormatter()
	{
		if (\is_null($this->formatter)) {
			$parameters = static::$defaultFormatterParameters;
			$this->formatter = new static::$defaultFormatter($parameters);
		}
		return $this->formatter;
	}

	/**
	 * Set log formatter
	 * @param Formatter\FormatterInterface $formatter
	 */
	public function setFormatter(Formatter\FormatterInterface $formatter)
	{
		$this->formatter = $formatter;
	}

	/**
	 * Set log filter
	 * @param Filter\FilterInterface $filter
	 * @param boolean $append
	 */
	public function addFilter(Filter\FilterInterface $filter, $append = true)
	{
		if ( ! $append) {
			$this->filters = array();
		}
		$this->filters[] = $filter;
	}

	/**
	 * Get appended filters
	 * @return Filter\FilterInterface[]
	 */
	public function getFilters()
	{
		return $this->filters;
	}
	
	/**
	 * Switch flag "writing" to false after unserialize
	 */
	public function __wakeup()
	{
		$this->writing = false;
	}
	
	/**
	 * Setting logger name
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Get the plugin configuration
	 * @return array
	 */
	public function getConfiguration()
	{
		return $this->parameters;
	}
	
	/**
	 * Magic call method for debug/info/etc
	 * @param string $method
	 * @param array $arguments
	 */
	function __call($method, $arguments)
	{
		// recursive call, breaking
		if ($this->writing) {
			return;
		}
		$this->writing = true;
		
		try {
			$level = strtoupper($method);
			if (!isset(Logger::$levels[$level])) {
				throw new Exception('Method ' . get_class($this) . '::' . $method . '() is not defined');
			}
			
			$params = array();
			if (isset($arguments[1])) {
				$offset = $arguments[1] + 1;
			} else {
				$offset = 1;
			}
			
			$params = Logger::getBacktraceInfo($offset);

			// Generate logger name
			$loggerName = null;
			if ($this->name != '') {
				$loggerName = $this->name;
			}
			if (isset($arguments[2]) && $arguments[2] != '') {
				if ($loggerName != '') {
					$loggerName .= ' ';
				}
				$loggerName .= $arguments[2];
			}

			$event = new Event($arguments[0], $level, $params['file'], $params['line'], $loggerName, $params);
			$this->write($event);
			
		} catch (Exception $e) {
			
			// not writing anymore
			$this->writing = false;
			
			throw $e;
		}
		
		// not writing anymore
		$this->writing = false;
	}
	
	/**
	 * Log event write method
	 * @param Event $event
	 */
	public function write(Event $event)
	{
		// filter acceptance test
		$filters = $this->getFilters();
		foreach ($filters as $filter) {
			$accept = $filter->accept($event);
			if ( ! $accept) {
				return;
			}
		}
		
		// format the message
		$event = $event->toArray();
		$this->getFormatter()->format($event);
		$this->_write($event);
		
	}
	
	/**
	 * Write the message
	 * @param array $event
	 */
	abstract protected function _write($event);
	
}