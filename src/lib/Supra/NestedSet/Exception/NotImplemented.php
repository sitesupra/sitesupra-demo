<?php

namespace Supra\NestedSet\Exception;

use LogicException;

/**
 * Not implemented functionality exception
 */
class NotImplemented extends LogicException implements INestedSetException
{
	/**
	 * Constructor
	 * @param string $message
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($message, $code = null, $previous = null)
	{
		$message = $message . ' is not implemented yet';
		parent::__construct($message, $code, $previous);
	}
}