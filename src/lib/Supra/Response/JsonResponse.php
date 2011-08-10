<?php

namespace Supra\Response;

/**
 * CMS specific JSON response
 */
class JsonResponse extends HttpResponse
{
	/**
	 * Response data
	 * @var array
	 */
	private $responseData;
	
	/**
	 * Error message
	 * @var string
	 */
	private $errorMessage;
	
	/**
	 * Confirmation message
	 * @var string
	 */
	private $confirmationMessage;
	
	/**
	 * Status message. Boolean true/false or 1/0
	 * @var boolean
	 */
	private $status = 1;
	
	/**
	 * If the data is already sent
	 * @var boolean
	 */
	private $dataSent = false;
	
	/**
	 * @param mixed $data 
	 */
	public function setResponseData($data)
	{
		$this->responseData = $data;
	}

	/**
	 * Sets error message
	 * @param string $errorMessage
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->errorMessage = $errorMessage;
	}

	/**
	 * Sets confirmation message
	 * @param string $confirmationMessage
	 */
	public function setConfirmationMessage($confirmationMessage)
	{
		$this->confirmationMessage = $confirmationMessage;
	}

	/**
	 * Sets response status. Boolean true/false or 1/0
	 * @param boolean $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}
			
	/**
	 * Do json encoding before passing to the parent, called internally only
	 * @param array $data
	 */
	public function output($data)
	{
		if ($this->dataSent) {
			throw new Exception\LogicException("Cannot output more then once");
		}
		
		$dataJson = json_encode($data);
		parent::output($dataJson);
		
		$this->dataSent = true;
	}
	
	/**
	 * Converts the output data into stream
	 */
	private function generateOutput()
	{
		$responseData = array(
			"status" => $this->status,
			"data" => $this->responseData,
			"error_message" => $this->errorMessage,
			"confirmation_message" => $this->confirmationMessage,
		);
				
		$this->output($responseData);
	}
	
	/**
	 * Flushes output
	 */
	public function flush()
	{
		$this->generateOutput();
		
		parent::flush();
	}
	
	/**
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$this->generateOutput();
		
		parent::flushToResponse($response);
	}

}
