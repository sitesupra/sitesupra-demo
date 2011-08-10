<?php

namespace Supra\Log\Plugin;

use Supra\Log\Log;

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
		if ( ! ($errno & error_reporting())) {
			return;
		}

		//Map PHP error codes to error handler codes
		switch($errno){
			case E_ERROR:
				Log::perror($errstr);
				break;
			case E_WARNING:
				Log::pwarn($errstr);
				break;
			case E_PARSE:
				Log::pfatal($errstr);
				break;
			case E_NOTICE:
				Log::pdebug($errstr);
				break;
			case E_CORE_ERROR:
				Log::pfatal($errstr);
				break;
			case E_CORE_WARNING:
				Log::pwarn($errstr);
				break;
			case E_COMPILE_ERROR:
				Log::pfatal($errstr);
				break;
			case E_COMPILE_WARNING:
				Log::pwarn($errstr);
				break;
			case E_USER_ERROR:
				Log::perror($errstr);
				break;
			case E_USER_WARNING:
				Log::pwarn($errstr);
				break;
			case E_USER_NOTICE:
				Log::pinfo($errstr);
				break;
			case E_STRICT:
				Log::pdebug($errstr);
				break;
			case E_RECOVERABLE_ERROR:
				Log::perror($errstr);
				break;
			default:
				Log::perror($errstr);
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