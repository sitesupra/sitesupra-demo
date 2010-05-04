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
}