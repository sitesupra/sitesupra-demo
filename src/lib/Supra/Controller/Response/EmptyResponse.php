<?php

namespace Supra\Controller\Response;

/**
 * Empty response class
 */
class EmptyResponse implements ResponseInterface
{
	/**
	 * Prepares the response
	 */
	public function prepare()
	{}

	/**
	 * Flush the output
	 */
	public function flush()
	{}

	/**
	 * Output method
	 * @param string $output
	 */
	public function output($output)
	{}

	/**
	 * Flush this response to the parent response
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{}
}