<?php

namespace Supra\Log\Formatter;

use Supra\Log\LogEvent;

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
		'format' => '[%time%] %level% %logger% - %file%(%line%): %subject%',
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
	 * @param LogEvent $event
	 */
	function format(LogEvent $event)
	{
		$eventData = $event->toArray();
		$format = $this->parameters['format'];
		$replaceWhat = array();
		$replaceWith = array();
		
		foreach ($this->parameters['variables'] as $variable) {
			// Special case for the time
			if ($variable == 'time') {
				$time = $event->formatTimestamp($this->parameters['timeFormat']);
				$replaceWhat[] = '%' . $variable . '%';
				$replaceWith[] = &$time;
			}
			if (array_key_exists($variable, $eventData)) {
				$replaceWhat[] = '%' . $variable . '%';
				$replaceWith[] = &$eventData[$variable];
			}
		}
		
		$message = str_replace($replaceWhat, $replaceWith, $format);
		$event->setMessage($message);
	}
	
}