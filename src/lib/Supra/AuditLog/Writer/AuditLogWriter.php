<?php

namespace Supra\AuditLog\Writer;

use Supra\Log\Writer\WriterAbstraction;
use Supra\AuditLog\AuditLogEvent;

/**
 * Audit log writer
 *
 */
class AuditLogWriter extends AuditLogWriterAbstraction
{

	/**
	 * Log writer class name
	 * @var string
	 */
	protected static $logWriterClassName = 'Supra\Log\Writer\NullWriter';

	/**
	 * Default writer parameters
	 * @var array
	 */
	public static $defaultWriterParameters = array();

	/**
	 * Default formatter
	 * @var string
	 */
	public static $defaultFormatter = 'Supra\AuditLog\Formatter\AuditLogFormatter';

	/**
	 * Default formatter parameters
	 * @var array
	 */
	public static $defaultFormatterParameters = array();

	/**
	 * Log writer instance
	 * @var WriterAbstraction
	 */
	protected $logWriter;

	/**
	 * Audit writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		$parameters = $parameters + static::$defaultWriterParameters;
		$this->logWriter = new static::$logWriterClassName($parameters);
		$formatter = new static::$defaultFormatter(static::$defaultFormatterParameters);
		$this->logWriter->setFormatter($formatter);
		$this->logWriter->setName('Audit');
	}

	/**
	 * Set log writer instance
	 * @param WriterAbstraction $logWriter 
	 */
	public function setLogWriter(WriterAbstraction $logWriter)
	{
		$this->logWriter = $logWriter;
	}

	/**
	 * Write to audit log
	 * @param string $level
	 * @param mixed $component
	 * @param string $action
	 * @param string $message
	 * @param mixed $user
	 * @param array $data 
	 */
	public function write($level, $component, $action, $message, $user = null, $data = array()) 
	{
		$loggerName = $this->name;
		$event = new AuditLogEvent($level, $component, $action, $message, $loggerName, $user, $data);
		$this->logWriter->write($event);
	}
	
}
