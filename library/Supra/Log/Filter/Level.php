<?php

namespace Supra\Log\Filter;

use Supra\Log\Logger;
use Supra\Log\Event;

/**
 * Log event filter by level
 */
class Level implements FilterInterface
{
	/**
	 * Filter default parameters
	 * @var array
	 */
	protected static $defaultParameters = array(
		'level' => Logger::DEBUG,
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
		
		if (isset(Logger::$levels[$this->parameters['level']])) {
			$this->parameters['levelPriority'] = Logger::$levels[$this->parameters['level']];
		} else {
			$this->parameters['levelPriority'] = 0;
		}
		
	}
	
	/**
	 * Filter method
	 * @param Event $event
	 * @return boolean
	 */
	function accept(Event $event)
	{
		return $this->parameters['levelPriority'] <= $event->getLevelPriority();
	}
}