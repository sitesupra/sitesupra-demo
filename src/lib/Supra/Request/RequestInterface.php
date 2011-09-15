<?php

namespace Supra\Request;

/**
 * Request interface
 */
interface RequestInterface
{
	/**
	 * Triggers request detail reading from the envoronment
	 */
	public function readEnvironment();
	
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
	public function getActions($limit);

	/**
	 * Get all actions as string joined by $glue argument value
	 * @param string $glue
	 * @return string
	 */
	public function getActionString($glue);
}