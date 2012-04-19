<?php

namespace Supra\AuditLog\Formatter;

use Supra\Log\Formatter\SimpleFormatter;

/**
 * Audit log formatter
 */
class AuditLogFormatter extends SimpleFormatter
{
	
	const FORMAT = '[%time%] %level% %component% %user% : %subject%';
	
	/**
	 * Configuration
	 * @var array
	 */
	protected static $defaultParameters = array(
		'format' => self::FORMAT,
		'timeFormat' => 'Y-m-d H:i:s',
	);
	
}