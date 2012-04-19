<?php

namespace Supra\Remote\Client;

use Supra\Remote\Client\ProxyOutput\ProxyOutput;

class RemoteCommandResponse
{

	/**
	 * @var ProxyOutput
	 */
	protected $proxyOutput;

	/**
	 * @var integer
	 */
	protected $resultCode;

	/**
	 * @var mixed
	 */
	protected $error;

	/**
	 * @var boolean
	 */
	protected $success;

	/**
	 * @return ProxyOutput
	 */
	public function getProxyOutput()
	{
		return $this->proxyOutput;
	}

	/**
	 * @param ProxyOutput $proxyOutput 
	 */
	public function setProxyOutput(ProxyOutput $proxyOutput)
	{
		$this->proxyOutput = $proxyOutput;
	}

	/**
	 * @return integer
	 */
	public function getResultCode()
	{
		return $this->resultCode;
	}

	/**
	 * @param integer $resultCode 
	 */
	public function setResultCode($resultCode)
	{
		$this->resultCode = $resultCode;
	}

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param mixed $error 
	 */
	public function setError($error)
	{
		$this->error = $error;
	}
	
	/**
	 * @return boolean
	 */
	public function getSuccess()
	{
		return $this->success;
	}
	
	/**
	 * @param boolean $success 
	 */
	public function setSuccess($success)
	{
		$this->success = $success;
	}



}

