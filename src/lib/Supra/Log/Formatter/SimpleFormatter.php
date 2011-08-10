<?php

namespace Supra\Log\Formatter;

use Supra\Log\Log;

/**
 * Simple log formatter - formats the message in custom format
 */
class SimpleFormatter implements FormatterInterface
{
	/**
	 * Formatter parameters
	 * @var array
	 */
	protected $parameters;

	/**
	 * Default configuration
	 * @var array
	 */
	protected static $defaultParameters = array(
		'format' => '[%time%] %level% %logger% - %file%(%line%): %message%',
		'timeFormat' => 'Y-m-d H:i:s',
	);
	
	/**
	 * Constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		$this->parameters = $parameters + static::$defaultParameters;
		
		// collect variables used in format
		preg_match_all('/%([^%]+)%/', $this->parameters['format'], $matches);
		$this->parameters['variables'] = $matches[1];
	}
	
	/**
	 * Format function
	 * @param array $event
	 */
	function format(array &$event)
	{
		$message = $this->parameters['format'];
		
		// don't do anything if format doesn't change the message
		if ($message == '%message%') return;
		
		foreach ($this->parameters['variables'] as $variable) {
			if ($variable == 'time') {
				$time = Log::getDateInDefaultTimezone($this->parameters['timeFormat'], $event['timestamp']);
				$replaceWhat[] = '%' . $variable . '%';
				$replaceWith[] = &$time;
			}
			if (array_key_exists($variable, $event)) {
				$replaceWhat[] = '%' . $variable . '%';
				$replaceWith[] = &$event[$variable];
			}
		}
		
		$event['message'] = str_replace($replaceWhat, $replaceWith, $message);
		
	}
	
}