<?php

namespace Supra\Controller;

use Exception;
use Supra\Response\HttpResponse;

/**
 * Description of ExceptionController
 */
class ExceptionController extends ControllerAbstraction
{
	/**
	 * @var Exception
	 */
	private $exception;
	
	/**
	 * @return Exception
	 */
	public function getException()
	{
		return $this->exception;
	}

	/**
	 * @param Exception $exception
	 */
	public function setException(Exception $exception)
	{
		$this->exception = $exception;
	}
	
	/**
	 * Ouput exception string
	 */
	public function execute()
	{
		$response = $this->getResponse();
		
		// HTTP response specifics
		if ($response instanceof HttpResponse) {
			$response->header("Content-Type", "text/plain");
			
			if ($this->exception instanceof namespace\Exception\ResourceNotFoundException) {
				$response->setCode(404);
			} else {
				$response->setCode(500);
			}
		}
		
		$response->output($this->exception->__toString());
	}
}
