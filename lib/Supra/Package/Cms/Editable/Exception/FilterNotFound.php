<?php

namespace Supra\Package\Cms\Editable\Exception;

use Supra\Package\Cms\Editable\Editable;

/**
 * Exception on filter not defined or found issues
 */
class FilterNotFound extends RuntimeException
{
	/**
	 * Generates the message automatically
	 * @param string $message
	 * @param Editable $editable
	 */
	public function __construct($message, Editable $editable)
	{
		$message = "{$message} for {$editable}";
		
		parent::__construct($message);
	}
}
