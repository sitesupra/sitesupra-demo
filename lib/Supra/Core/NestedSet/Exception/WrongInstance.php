<?php

namespace Supra\Core\NestedSet\Exception;

/**
 * Error of wrong object instance received
 */
class WrongInstance extends \LogicException implements NestedSetException
{
	/**
	 * @var string
	 */
	const MESSAGE_FORMAT = 'Wrong object received, "Supra\Core\NestedSet\%1$s" expected "%2$s" received';

	/**
	 * Constructor
	 * @param \stdClass $receivedObject
	 * @param string $expected
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($receivedObject, $expected, $code = null, $previous = null)
	{
		$received = get_class($receivedObject);
		$message = sprintf(static::MESSAGE_FORMAT, $expected, $received);
		parent::__construct($message, $code, $previous);
	}

}