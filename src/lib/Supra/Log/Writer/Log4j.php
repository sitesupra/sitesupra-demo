<?php

namespace Supra\Log\Writer;

/**
 * Log4j log writer
 */
class Log4j extends Socket
{
	/**
	 * Default formatter
	 * @var string
	 */
	public static $defaultFormatter = 'Supra\Log\Formatter\Log4j';

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array();

	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'host' => 'tcp://127.0.0.1',
		'port' => 4445,
		'timeout' => 0.1,
	);
}