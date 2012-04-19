<?php

namespace Supra\Response;

/**
 * Response storing output in array
 */
class ArrayResponse implements ResponseInterface
{
	/**
	 * Output buffer
	 * @var array
	 */
	private $output = array();
	
	/**
	 * Not implemented for this type of response
	 */
	public function flush()
	{
		throw new Exception\LogicException("Flush is not defined for ArrayResponse");
	}

	/**
	 * {@inheritdoc}
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$response->output($this);
	}

	/**
	 * {@inheritdoc}
	 * @param string $output
	 */
	public function output($output)
	{
		$this->output[] = $output;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare()
	{
		
	}
	
	/**
	 * Get output string
	 * @return string
	 */
	public function getOutputString()
	{
		$output = implode($this->output);
		
		return $output;
	}
	
	public function __toString()
	{
		return $this->getOutputString();
	}
}
