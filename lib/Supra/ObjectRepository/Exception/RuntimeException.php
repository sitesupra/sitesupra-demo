<?php

namespace Supra\ObjectRepository\Exception;

/**
 * RuntimeException
 */
class RuntimeException extends \RuntimeException implements ObjectRepositoryException
{
	/**
	 * @param string $caller
	 * @param string $interface
	 * @return RuntimeException @return
	 */
	public static function objectNotFound($caller, $interface)
	{
		return new self("Object '$interface' not found for $caller");
	}
}
