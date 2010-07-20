<?php

namespace Supra\Log\Plugin;

use Supra\Log\Logger;

/**
 * PHP log handler
 */
class PhpErrorHandler
{
	/**
	 * Main method - defines error handler functions
	 */
	public function __invoke()
	{
		set_error_handler(array($this, 'handleError'));
		set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Error and exception handler
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @return null
	 */
	public function handleError($errno, $errstr = null, $errfile = null, $errline = null)
	{
		// Filter by system's error reporting level
		if (!($errno & error_reporting())) {
			return;
		}

		//Map PHP error codes to error handler codes
		switch($errno){
			case E_ERROR:
				Logger::perror($errstr);
				break;
			case E_WARNING:
				Logger::pwarn($errstr);
				break;
			case E_PARSE:
				Logger::pfatal($errstr);
				break;
			case E_NOTICE:
				Logger::pdebug($errstr);
				break;
			case E_CORE_ERROR:
				Logger::pfatal($errstr);
				break;
			case E_CORE_WARNING:
				Logger::pwarn($errstr);
				break;
			case E_COMPILE_ERROR:
				Logger::pfatal($errstr);
				break;
			case E_COMPILE_WARNING:
				Logger::pwarn($errstr);
				break;
			case E_USER_ERROR:
				Logger::perror($errstr);
				break;
			case E_USER_WARNING:
				Logger::pwarn($errstr);
				break;
			case E_USER_NOTICE:
				Logger::pinfo($errstr);
				break;
			case E_STRICT:
				Logger::pdebug($errstr);
				break;
			case E_RECOVERABLE_ERROR:
				Logger::perror($errstr);
				break;
			default:
				Logger::perror($errstr);
				break;
		}
	}

	/**
	 * Handle unaught exception
	 * @param \Exception $exception
	 * TODO: when handling uncaught exception the debug_backtrace does not
	 *		contain the file:line the exception was thrown.
	 */
	public function handleException($exception)
	{
		$exceptionString = $exception->__toString();
		self::handleError(E_ERROR, $exceptionString);
	}
}