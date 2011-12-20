<?php

namespace Supra\AuditLog\Writer;

use Supra\Log\Exception;
use Supra\Log\Writer\WriterAbstraction;
use Supra\AuditLog\AuditLogEvent;

/**
 * Audit log writer abstraction
 * 
 * @method void dump(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void debug(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void info(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void warn(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void error(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void fatal(mixed $component, string $action, string $message, mixed $user, array $data)
 */
abstract class AuditLogWriterAbstraction
{

	/**
	 * Log writer class name
	 * @var string
	 */
	protected static $logWriterClassName = 'Supra\Log\Writer\NullWriter';

	/**
	 * Default writer parameters
	 * @var array
	 */
	public static $defaultWriterParameters = array();

	/**
	 * Default formatter
	 * @var string
	 */
	public static $defaultFormatter = 'Supra\AuditLog\Formatter\AuditLogFormatter';

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array();

	/**
	 * Log writer instance
	 * @var WriterAbstraction
	 */
	protected $logWriter;

	/**
	 * Audit writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		$parameters = $parameters + static::$defaultWriterParameters;
		$this->logWriter = new static::$logWriterClassName($parameters);
		$formatter = new static::$defaultFormatter(static::$defaultFormatterParameters);
		$this->logWriter->setFormatter($formatter);
		$this->logWriter->setName('Audit');
	}

	/**
	 * Set log writer instance
	 * @param WriterAbstraction $logWriter 
	 */
	protected function setLogWriter(WriterAbstraction $logWriter)
	{
		$this->logWriter = $logWriter;
	}

	/**
	 * Magic call method for debug/info/etc
	 * @param string $method
	 * @param array $arguments
	 */
	public function __call($method, $arguments)
	{
		try {
			$level = strtoupper($method);
			
			if ( ! isset(AuditLogEvent::$levels[$level])) {
				throw Exception\LogicException::badLogLevel($method);
			}
			
			// Generate logger name
			$loggerName = $this->name;

			$component = $arguments[0];
			$action = $arguments[1];
			$message = $arguments[2];
			$user = null;
			if (isset($arguments[3])) {
				$user = $arguments[3];
			}
			$data = null;
			if (isset($arguments[4])) {
				$data = $arguments[4];
			}

			$event = new AuditLogEvent($level, $component, $action, $message, $loggerName, $user, $data);
			$this->logWriter->write($event);
			
		} catch (\Exception $e) {
			
			// Try bootstrap logger if the current fails
			$bootstrapLogger = Log::getBootstrapLogger();
			
			if ($bootstrapLogger != $this) {
				
				// Log the exception
				$bootstrapLogger->error($e);
			} else {
				
				// Bootstrap failed
				throw $e;
			}
		}
	}
	
}
