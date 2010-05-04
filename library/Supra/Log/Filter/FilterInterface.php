<?php

namespace Supra\Log\Filter;

use Supra\Log\Event;

/**
 * Log event filter interface
 */
interface FilterInterface
{
	
	/**
	 * Construct method
	 * @param array $parameters
	 */
	function __construct(array $parameters = array());
	
	/**
	 * Filter method
	 * @param array $event
	 * @return boolean
	 */
	function accept(Event $event);
	
}