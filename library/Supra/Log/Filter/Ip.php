<?php

namespace Supra\Log\Filter;

use Supra\Log\Event;
use Supra\Log\Logger;
use Supra\Ip\Range;

/**
 * Log event filter by client IP address
 */
class Ip implements FilterInterface
{
	/**
	 * Filter default parameters
	 * @var array
	 */
	protected static $defaultParameters = array(
		'range' => '127.*',
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
		if ( ! ($this->parameters['range'] instanceof Range)) {
			$this->parameters['range'] = new Range($this->parameters['range']);
		}
	}
	
	/**
	 * Filter method
	 * @param Event $event
	 * @return boolean
	 */
	function accept(Event $event)
	{
		if (empty($_SERVER['REMOTE_ADDR'])) {
			return false;
		}
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		if (empty($ip)) {
			return false;
		}
		return $this->parameters['range']->includes($ip);
	}
	
}