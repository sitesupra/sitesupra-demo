<?php

namespace Supra\Log\Filter;

use Supra\Log\LogEvent;

/**
 * Log event filter by level
 */
class LevelFilter implements FilterInterface
{
	/**
	 * Filter default parameters
	 * @var array
	 */
	protected static $defaultParameters = array(
		'level' => LogEvent::DEBUG,
	);

	/**
	 * Configuration
	 * @var array
	 */
	protected $parameters = array();
	
	/**
	 * Construct method
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		$this->parameters = $parameters + static::$defaultParameters;
		
		$this->parameters['level'] = strtoupper($this->parameters['level']);
		
		if (isset(LogEvent::$levels[$this->parameters['level']])) {
			$this->parameters['levelPriority'] = LogEvent::$levels[$this->parameters['level']];
		} else {
			$this->parameters['levelPriority'] = 0;
		}
		
	}
	
	/**
	 * Filter method
	 * @param LogEvent $event
	 * @return boolean
	 */
	function accept(LogEvent $event)
	{
		return $this->parameters['levelPriority'] <= $event->getLevelPriority();
	}
}