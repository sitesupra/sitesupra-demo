<?php

namespace Supra\Log\Formatter;

/**
 * Log event formatter interface
 */
interface FormatterInterface
{
	
	/**
	 * Construct method
	 * @param array $parameters
	 */
	function __construct(array $parameters = array());
	
	/**
	 * Format method
	 * @param array $event
	 */
	function format(array &$event);
	
}