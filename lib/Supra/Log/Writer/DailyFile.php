<?php

namespace Supra\Log\Writer;

/**
 * Daily file log writer
 */
class DailyFile extends File
{
	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => 'supra.%date%.log',
		'dateFormat' => 'Ymd',
	);
}