<?php

namespace Supra\Controller\Request;

/**
 * CLI request object
 */
class Cli implements RequestInterface
{
	/**
	 * List of actions passed as the additional parameters
	 * @var array
	 */
	protected $actions;

	/**
	 * Request parameters
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		//TODO: parse parameters
		global $argv;
		$this->setActions(array_slice($argv, 1));
	}

	/**
	 * Get action list
	 * @param integer $limit
	 * @return string[]
	 */
	public function getActions($limit = null)
	{
		$actions = $this->actions;
		if ($limit > 0) {
			return array_slice($actions, 0, $limit);
		} else {
			return $actions;
		}
	}

	/**
	 * Get request parameter
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getParameter($key, $default = null)
	{
		if (array_key_exists($key, $this->parameters)) {
			return $this->parameters[$key];
		}
		return $default;
	}

	/**
	 * Set request actions
	 * @param array $actions
	 */
	public function setActions($actions)
	{
		$this->actions = $actions;
	}

	/**
	 * Get all actions as string joined by $glue argument value
	 * @param string $glue
	 * @return string
	 */
	public function getActionString($glue = ' ')
	{
		return implode($glue, $this->getActions());
	}

}