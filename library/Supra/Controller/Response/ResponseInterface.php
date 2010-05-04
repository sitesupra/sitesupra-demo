<?php

namespace Supra\Controller\Response;

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
}