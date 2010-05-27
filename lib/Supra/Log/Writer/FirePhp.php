<?php

namespace Supra\Log\Writer;

use Supra\Log\Logger;

require_once SUPRA_LIBRARY_PATH . 'FirePhp/FirePHP.class.php';

/**
 * FirePHP log writer
 */
class FirePhp extends WriterAbstraction
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
	public static $defaultFormatterParametrs = array(
		'format' => '%logger% - %message%',
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
			case Logger::DEBUG:	$method = \FirePHP::LOG; break;
			case Logger::INFO:	$method = \FirePHP::INFO; break;
			case Logger::WARN:	$method = \FirePHP::WARN; break;
			case Logger::ERROR:	$method = \FirePHP::ERROR; break;
			case Logger::FATAL:	$method = \FirePHP::ERROR; break;
			default: 			$label = $event['level'];
		}
		
		$options = array(
			'File' => $event['file'],
			'Line' => $event['line'],
		);

		$this->fp->fb($event['message'], $label, $method, $options);
		
	}
	
}