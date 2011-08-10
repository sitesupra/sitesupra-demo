<?php

namespace Supra\Log\Writer;

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
		'format' => '%level% %logger% - %file%(%line%): %message%',
		'timeFormat' => 'Y-m-d H:i:s',
	);
	
	/**
	 * Write the event in the system log
	 * @param array $event
	 */
	protected function _write($event)
	{
		error_log($event['message']);
	}
	
}