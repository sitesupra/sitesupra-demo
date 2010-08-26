<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Request\Cli;

/**
 * Router based on action provided in
 */
class CliAction extends RouterAbstraction
{
	/**
	 * Base priority value
	 * @var integer
	 */
	protected static $basePriority = 50;
	
	/**
	 * Action and subactions to bind to
	 * @var array
	 */
	protected $actions;

	/**
	 * Router depth
	 * @var integer
	 */
	protected $depth;

	/**
	 *
	 * @param string $action
	 * @param string $subAction1
	 * @param string $subAction2
	 * ...
	 */
	public function  __construct($action, $subAction1 = null, $subAction2 = null)
	{
		$this->setActions(func_get_args());
	}

	/**
	 * Set router actions
	 * @param array $actions
	 */
	protected function setActions($actions)
	{
		$this->actions = $actions;
		$this->depth = count($this->actions);
	}

	/**
	 * Whether the router matches the request
	 * @param RequestInterface $request
	 */
    public function match(RequestInterface $request)
	{
		if ( ! ($request instanceof Cli)) {
			\Log::sdebug('Not the instance of Request\Cli');
			return false;
		}

		$requestActions = $request->getActions(null);

		if ($this->depth > count($requestActions)) {
			\Log::sdebug('Router depth exceeds request action count, does not match for router ', $this->__toString());
			return false;
		}

		foreach ($this->actions as $key => $action) {
			if ($requestActions[$key] != $action) {
				\Log::sdebug("Request did not match router because of difference in action #{$key} for router ", $this->__toString());
				return false;
			}
		}
		\Log::sdebug("Request match router ", $this->__toString());
		return true;
	}

	/**
	 * Get router priority
	 * @return integer
	 */
	public function getPriority()
	{
		return static::$basePriority + $this->depth;
	}

	/**
	 * Represents the router as string
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__ . ':' . implode('/', $this->actions);
	}
}