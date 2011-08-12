<?php

namespace Supra\Log\Exception;

/**
 * Log RuntimeException
 */
class RuntimeException extends \RuntimeException implements LogException
{
	/**
	 * @param string $configurationName
	 * @return RuntimeException 
	 */
	public static function emptyConfiguration($configurationName)
	{
		return new self("Parameter $configurationName is not provided");
	}
}
