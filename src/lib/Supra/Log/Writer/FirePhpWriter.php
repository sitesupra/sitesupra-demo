<?php

namespace Supra\Log\Writer;

use Supra\Log\Log;

require_once SUPRA_LIBRARY_PATH . 'FirePhp/FirePHP.class.php';

/**
 * FirePHP log writer
 */
class FirePhpWriter extends WriterAbstraction
{
	
	/**
	 * FirePHP instance cache - only one FirePHP logger instance for all loggers can be defined
	 * @var \FirePHP
	 */
	protected static $_fp;
	
	/**
	 * FirePHP instance
	 * @var \FirePHP
	 */
	protected $fp;

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array(
		'format' => '%message%',
		'timeFormat' => 'Y-m-d H:i:s',
	);

	/**
	 * Constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		parent::__construct($parameters);
		
		if (is_null(self::$_fp)) {
			self::$_fp = new \FirePHP();
		}
		$this->fp = self::$_fp;
	}
	
	/**
	 * Write the event in the fire php instance
	 * @param array $event
	 */
	protected function _write($event)
	{
		$label = null;
		$method = \FirePHP::LOG;
		
		switch ($event['level']) {
			case Log::DEBUG:	$method = \FirePHP::LOG; break;
			case Log::INFO:		$method = \FirePHP::INFO; break;
			case Log::WARN:		$method = \FirePHP::WARN; break;
			case Log::ERROR:	$method = \FirePHP::ERROR; break;
			case Log::FATAL:	$method = \FirePHP::ERROR; break;
			default: 			$label = $event['level'];
		}
		
		$options = array(
			'File' => $event['file'],
			'Line' => $event['line'],
		);

		$this->fp->fb($event['message'], $label, $method, $options);
		
	}
	
}