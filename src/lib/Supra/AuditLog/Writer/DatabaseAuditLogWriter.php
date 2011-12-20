<?php

namespace Supra\AuditLog\Writer;

/**
 * Database audit log writer
 * 
 */
class DatabaseAuditLogWriter extends AuditLogWriterAbstraction
{
	/*
	 * TODO: Currently null writer. To be implemented.
	 */

	/**
	 * Log writer class name
	 * @var string
	 */
	protected static $logWriterClassName = 'Supra\Log\Writer\NullWriter';

}