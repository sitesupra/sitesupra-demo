<?php

class LoaderTest
{
	private $errorHandler;
	private $errorCaught = false;
	private $loadRequests = array();
	
	/**
	 * Sets error handler, autoloader, configures assert options
	 */
	public function __construct()
	{
		$this->errorHandler = set_error_handler(array($this, 'handleError'));
		spl_autoload_register(array($this, 'load'));
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_CALLBACK, array($this, 'assertionFail'));
	}
	
	/**
	 * Marks the error caught, should trigger autoloader (it DOESN'T)
	 */
	public function handleError($errno, $errstr, $errfile, $errline)
	{
		echo "Caught error '$errstr'\n";
		$this->errorCaught = true;
		class_exists('DoesNotExist_2', true);
	}
	
	/**
	 * Remembers autoload requests
	 */
	public function load($name)
	{
		echo "Tried to load class '$name'\n";
		$this->loadRequests[$name] = true;
	}
	
	/**
	 * Assertion handler
	 * @throws RuntimeException
	 */
	public function assertionFail($file, $line, $code)
	{
		throw new RuntimeException("Assertion failed on line '$line'\n");
	}
	
	/**
	 * Main function
	 */
	public function testFailure()
	{
		// This creates load request to class DoesNotExist_1
		class_exists('DoesNotExist_1', true);
		
		// Make sure our autoloader received the request
		assert(array_key_exists('DoesNotExist_1', $this->loadRequests));
		
		// This creates PHP warning about private __call method, BUT 
		require_once __DIR__ . '/ClassWithBadMagicCall.php';
		
		// Makes sure our error handler caught the warning
		assert($this->errorCaught);
		
		// FAILS here
		assert(array_key_exists('DoesNotExist_2', $this->loadRequests));
	}
}
