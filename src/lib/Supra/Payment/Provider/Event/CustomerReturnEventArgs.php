<?php

namespace Supra\Payment\Provider\Event;

use Supra\Response\ResponseInterface;

class CustomerReturnEventArgs extends EventArgsAbstraction
{
	/**
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * @param ResponseInterface $response 
	 */
	public function setResponse(ResponseInterface $response)
	{
		$this->response = $response;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}

}

