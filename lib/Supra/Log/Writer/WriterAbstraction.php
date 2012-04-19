<?php

namespace Supra\Log\Writer;

use Supra\Log\Filter;
use Supra\Log\Formatter;
use Supra\Log\Exception;
use Supra\Log\Log;
use Supra\Log\LogEvent;

/**
 * Abstract for log writer
 * @method void dump(mixed $argument)
 * @method void debug(mixed $argument)
 * @method void info(mixed $argument)
 * @method void warn(mixed $argument)
 * @method void error(mixed $argument)
 * @method void fatal(mixed $argument)
 */
abstract class WriterAbstraction
{
	/**
	 * Internal backtrace length
	 */
	const BACKTRACE_OFFSET_START = 1;
	
	/**
	 * Formatter instance
	 * @var Formatter\FormatterInterface
	 */
	protected $formatter;
	
	/**
	 * Filter instance
	 * @var Filter\FilterInterface[]
	 */
	protected $filters = array();
	
	/**
	 * Configuration
	 * @var array
	 */
	protected $parameters;
	
	/**
	 * Logger name
	 * @var string
	 */
	protected $name;
	
	/**
	 * Offset parameter for backtrace
	 * @var int
	 */
	protected $backtraceOffset = self::BACKTRACE_OFFSET_START;
	
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
	public static $defaultFormatter = 'Supra\Log\Formatter\SimpleFormatter';

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
		if (is_null($this->formatter)) {
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
	 * Function to be called after successfull or not successfull log write event
	 */
	protected function reset()
	{
		$this->backtraceOffset = self::BACKTRACE_OFFSET_START;
		
		// not writing anymore
		$this->writing = false;
	}
	
	/**
	 * Magic call method for debug/info/etc
	 * @param string $method
	 * @param array $arguments
	 */
	public function __call($method, $arguments)
	{
		// recursive call, trying the bootstrap logger
		if ($this->writing) {
			$bootstrapLogger = Log::getBootstrapLogger();
			
			if ($bootstrapLogger != $this) {
				$bootstrapLogger->increaseBacktraceOffset(2);
				$bootstrapLogger->__call($method, $arguments);
			}
			return;
		}
		
		$this->writing = true;
		
		try {
			$level = strtoupper($method);
			
			// Special case for dump function
			if ($level == 'DUMP') {
				$level = LogEvent::DEBUG;
				ob_start();
				foreach ($arguments as &$arg) {
					var_dump($arg);
				}
				$arguments = array(ob_get_clean());
			}
			
			if ( ! isset(LogEvent::$levels[$level])) {
				/*
				 * This exception will break out from the logger for sure 
				 * because bootstrap logger will not have this level as well
				 */
				throw Exception\LogicException::badLogLevel($method);
			}
			
			$params = LogEvent::getBacktraceInfo($this->backtraceOffset);

			// Generate logger name
			$loggerName = $this->name;

			$event = new LogEvent($arguments, $level, $params['file'], $params['line'], $loggerName, $params);
			$this->write($event);
			
		} catch (\Exception $e) {
			$this->reset();
			
			// Try bootstrap logger if the current fails
			$bootstrapLogger = Log::getBootstrapLogger();
			
			if ($bootstrapLogger != $this) {
				
				// Log the exception
				$bootstrapLogger->error($e);
				
				// Log the initial log event
				$bootstrapLogger->increaseBacktraceOffset($this->backtraceOffset + 1);
				$bootstrapLogger->__call($method, $arguments);
			} else {
				
				// Bootstrap failed
				throw $e;
			}
		}
		
		$this->reset();
	}
	
	/**
	 * @param int $byOffset
	 */
	public function increaseBacktraceOffset($byOffset = 1)
	{
		$this->backtraceOffset += $byOffset;
	}
	
	/**
	 * Log event write method
	 * @param LogEvent $event
	 */
	public function write(LogEvent $event)
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
		$this->getFormatter()->format($event);
		$this->_write($event);
	}
	
	/**
	 * Write the message
	 * @param LogEvent $event
	 */
	abstract protected function _write(LogEvent $event);
}
