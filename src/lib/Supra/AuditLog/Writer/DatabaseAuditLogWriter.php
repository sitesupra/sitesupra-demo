<?php

namespace Supra\AuditLog\Writer;

/**
 * Database audit log writer
 * 
 */
class DatabaseAuditLogWriter extends AuditLogWriterAbstraction
{
	/*
	 * TODO: Currently does nothing. To be implemented.
	 */
	public function write($level, $component, $action, $message, $user = null, $data = array()) 
	{
		1+1;
	}
}