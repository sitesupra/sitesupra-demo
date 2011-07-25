<?php

namespace Supra\Response;

/**
 * CMS specific JSON response
 */
class JsonResponse extends Http
{
	/**
	 * Response data
	 * @var array
	 */
	private $responseData;
	
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
		//TODO: add all properties
		$responseData = array(
			"status" => 1,
			"data" => $this->responseData,
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
