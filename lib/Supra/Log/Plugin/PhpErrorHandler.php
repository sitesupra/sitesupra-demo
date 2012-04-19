<?php

namespace Supra\Log\Plugin;

use Supra\ObjectRepository\ObjectRepository;

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
		
		$log = ObjectRepository::getLogger($this);
		
		$log->increaseBacktraceOffset();

		//Map PHP error codes to error handler codes
		switch($errno){
			case E_ERROR:
				$log->error($errstr);
				break;
			case E_WARNING:
				$log->warn($errstr);
				break;
			case E_PARSE:
				$log->fatal($errstr);
				break;
			case E_NOTICE:
				$log->debug($errstr);
				break;
			case E_CORE_ERROR:
				$log->fatal($errstr);
				break;
			case E_CORE_WARNING:
				$log->warn($errstr);
				break;
			case E_COMPILE_ERROR:
				$log->fatal($errstr);
				break;
			case E_COMPILE_WARNING:
				$log->warn($errstr);
				break;
			case E_USER_ERROR:
				$log->error($errstr);
				break;
			case E_USER_WARNING:
				$log->warn($errstr);
				break;
			case E_USER_NOTICE:
				$log->info($errstr);
				break;
			case E_STRICT:
				$log->debug($errstr);
				break;
			case E_RECOVERABLE_ERROR:
				$log->error($errstr);
				break;
			default:
				$log->error($errstr);
				break;
		}
	}

	/**
	 * Handle unaught exception
	 * @param \Exception $exception
	 * TODO: when handling uncaught exception the debug_backtrace does not
	 *		contain the file:line the exception was thrown.
	 */
	public function handleException(\Exception $exception)
	{
		$exceptionString = $exception->__toString();
		$this->handleError(E_ERROR, $exceptionString);
	}
}