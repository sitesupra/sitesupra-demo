<?php

namespace Supra\AuditLog\Writer;

use Supra\Log\Exception;
use Supra\AuditLog\AuditLogEvent;

/**
 * Audit log writer abstraction
 * 
 * @method void dump(mixed $component, string string $message, mixed $user, array $data)
 * @method void debug(mixed $component, string string $message, mixed $user, array $data)
 * @method void info(mixed $component, string string $message, mixed $user, array $data)
 * @method void warn(mixed $component, string string $message, mixed $user, array $data)
 * @method void error(mixed $component, string string $message, mixed $user, array $data)
 * @method void fatal(mixed $component, string string $message, mixed $user, array $data)
 */
abstract class AuditLogWriterAbstraction
{

	/**
	 * Write to audit log
	 * @param string $level
	 * @param mixed $component
	 * @param string $message
	 * @param mixed $user
	 * @param array $data 
	 */
	abstract public function write($level, $component, $message, $user = null, $data = array());

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
			
			$component = $arguments[0];
			$message = $arguments[1];
			$user = null;
			if (isset($arguments[2])) {
				$user = $arguments[2];
			}
			$data = null;
			if (isset($arguments[3])) {
				$data = $arguments[3];
			}

			$this->write($level, $component, $message, $user, $data);
			
		} catch (\Exception $e) {
			
			// Try bootstrap logger to log the exception
			$bootstrapLogger = Log::getBootstrapLogger();
			$bootstrapLogger->error($e);
		}
	}
	
}
