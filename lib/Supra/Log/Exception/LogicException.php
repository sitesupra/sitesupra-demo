<?php

namespace Supra\Log\Exception;

/**
 * Log LogicException
 */ 
class LogicException extends \LogicException implements LogException
{
	public static function badLogLevel($level)
	{
		return new self("Bad log level '{$level}' received");
	}
}
