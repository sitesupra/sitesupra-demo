<?php

namespace Supra\NestedSet\Exception;

/**
 * 
 */
class NotImplemented extends Exception
{
	public function __construct($message, $code = null, $previous = null)
	{
		$message = $message . ' is not implemented yet';
		parent::__construct($message, $code, $previous);
	}
}