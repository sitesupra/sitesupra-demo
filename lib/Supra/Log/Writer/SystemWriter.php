<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;

/**
 * System log writer
 */
class SystemWriter extends WriterAbstraction
{
	/**
	 * Default configuration
	 *
	 * @var array
	 */
	public static $defaultFormatterParameters = array(
		'format' => '%level% %logger% - %file%(%line%): %subject%',
		'timeFormat' => 'Y-m-d H:i:s',
	);
	
	/**
	 * Write the event in the system log
	 * @param LogEvent $event
	 */
	protected function _write(LogEvent $event)
	{
		error_log($event->getMessage());
	}
	
}