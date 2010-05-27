<?php

namespace Supra\Controller\Request;

/**
 * Request interface
 */
interface RequestInterface
{
	/**
	 * Get request parameter
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getParameter($key, $default = null);

	/**
	 * Get action list
	 * @param integer $limit
	 * @return string[]
	 */
	public function getAction($limit = null);
}