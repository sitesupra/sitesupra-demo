<?php

namespace Supra\Controller;

use Exception;
use Supra\Response\HttpResponse;
use Supra\Authorization\Exception\AccessDeniedException;

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
				$response->output("404 PAGE NOT FOUND\n");
			} else if ($this->exception instanceof AccessDeniedException) {
				$response->setCode(403);
				$response->output("403 FORBIDDEN\n");
			} else {

				$exceptionIdentifier = md5((string) $this->exception);

				$response->setCode(500);
				$response->output(SUPRA_ERROR_MESSAGE . ' #' . $exceptionIdentifier . "\n");
			}
		}

//		$response->output("\n" . $this->exception->__toString());
	}

}
