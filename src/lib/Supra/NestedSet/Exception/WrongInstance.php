<?php

namespace Supra\NestedSet\Exception;

/**
 * 
 */
class WrongInstance extends Exception
{
	const MESSAGE_FORMAT = 'Wrong object received, "Supra\NestedSet\%1$s" expected "%2$s" received';

	public function __construct($receivedObject, $expected, $code = null, $previous = null)
	{
		$received = \get_class($receivedObject);
		$message = sprintf(static::MESSAGE_FORMAT, $expected, $received);
		parent::__construct($message, $code, $previous);
	}

}