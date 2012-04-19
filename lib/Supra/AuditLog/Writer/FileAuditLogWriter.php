<?php

namespace Supra\AuditLog\Writer;

/**
 * File audit log writer
 * 
 */
class FileAuditLogWriter extends AuditLogWriter
{

	/**
	 * Log writer class name
	 * @var string
	 */
	protected static $logWriterClassName = 'Supra\Log\Writer\FileWriter';

	/**
	 * Default writer parameters
	 * @var array
	 */
	public static $defaultWriterParameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => 'audit.log',
	);

}