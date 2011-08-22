<?php

namespace Supra\Response;

/**
 * Response interface
 */
interface ResponseInterface
{
	/**
	 * Prepares the response
	 */
	public function prepare();
	
	/**
	 * Flush the response
	 */
	public function flush();

	/**
	 * Output method
	 * @param string $output
	 */
	public function output($output);

	/**
	 * Flush this response to the parent response
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response);
}