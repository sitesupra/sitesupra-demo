<?php

namespace Supra\Cms\Exception;

/**
 * Thrown when CMS action must be terminated immediately
 */
class StopExecutionException extends \RuntimeException
{
	public function __construct($message = '', $code = null, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
