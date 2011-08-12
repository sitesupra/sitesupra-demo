<?php

namespace Supra\Log;

/**
 * Static logger class
 * @method void dump(mixed $argument)
 * @method void debug(mixed $argument)
 * @method void info(mixed $argument)
 * @method void warn(mixed $argument)
 * @method void error(mixed $argument)
 * @method void fatal(mixed $argument)
 */
class Log
{
	/**
	 * Create logger for bootstrap
	 * @return Writer\SystemWriter
	 */
	public static function getBootstrapLogger()
	{
		$logger = new Writer\SystemWriter();
		$logger->setName('Bootstrap');
		
		return $logger;
	}
	
	/**
	 * Magic static method for calling dump(), debug(), etc methods
	 * @param string $name
	 * @param array $arguments
	 */
	public static function __callStatic($name,  $arguments)
	{
		// find appropriate logger
		//TODO: search by caller
		$log = \Supra\ObjectRepository\ObjectRepository::getLogger(__CLASS__);
		$log->increaseBacktraceOffset(3);
		call_user_func_array(array($log, $name), $arguments);
	}

}
