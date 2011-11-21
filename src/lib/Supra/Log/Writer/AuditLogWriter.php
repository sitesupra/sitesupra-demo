<?php

namespace Supra\Log\Writer;

use Supra\Log\AuditLogEvent;

/**
 * Stream log writer
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

			$data = $arguments[0];
			$component = $arguments[1];
			$action = $arguments[2];
			$user = $arguments[3];

			$event = new AuditLogEvent($data, $level, $component, $action, $user, $loggerName);
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