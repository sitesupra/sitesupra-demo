<?php

namespace Supra\Log\Formatter;

use Supra\Log\LogEvent;

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
	function format(LogEvent $event);
	
}