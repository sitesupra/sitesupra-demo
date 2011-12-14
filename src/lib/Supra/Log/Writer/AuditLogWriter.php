<?php

namespace Supra\Log\Writer;

use Supra\Log\AuditLogEvent;

/**
 * Audit log writer
 * 
 * @method void dump(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void debug(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void info(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void warn(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void error(mixed $component, string $action, string $message, mixed $user, array $data)
 * @method void fatal(mixed $component, string $action, string $message, mixed $user, array $data)
 */
class AuditLogWriter extends FileWriter
{
	/**
	 * Default formatter
	 * @var string
	 */
	public static $defaultFormatter = 'Supra\Log\Formatter\AuditLogFormatter';

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array();

	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => 'audit.log',
	);
	
	/**
	 * Log writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		// parent constructor
		parent::__construct($parameters);

		$this->setName('Audit');
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
			$this->write($event);
			
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