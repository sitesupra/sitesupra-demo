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
	 * Additional response parts
	 * @var array
	 */
	private $responseParts = array();
	
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
	 * Allows pushing values to the response data array
	 * @param mixed $data
	 * @throws Exception\RuntimeException if response data is not an array
	 */
	public function appendResponseData($data)
	{
		if (is_null($this->responseData)) {
			$this->responseData = array();
		}
		
		if ( ! is_array($this->responseData)) {
			throw new Exception\RuntimeException("Cannot append data to JsonResponse, data is not an array");
		}
		
		$this->responseData[] = $data;
	}

	/**
	 * Sets error message
	 * @param string $errorMessage
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->errorMessage = $errorMessage;
		$this->status = 0;
	}

	/**
	 * Add aditional response part in main JSON object
	 * @param string $name
	 * @param mixed $value
	 */
	public function addResponsePart($name, $value)
	{
		$this->responseParts[$name] = $value;
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
		$response = array(
			"status" => $this->status,
			"data" => $this->responseData,
			"error_message" => $this->errorMessage,
		);
		
		// Append other parts, don't overwrite existing
		if (is_array($this->responseParts)) {
			$response += $this->responseParts;
		}
				
		$this->output($response);
	}
	
	/**
	 * Flushes output
	 */
	public function flush()
	{
		$this->generateOutput();
		
		parent::flush();
	}
	
}
