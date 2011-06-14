<?php

namespace Supra\Editable\Exception;

use Supra\Editable\EditableInterface;

/**
 * Exception on filter not defined or found issues
 */
class FilterNotFound extends RuntimeException implements EditableException
{
	/**
	 * Generates the message automatically
	 * @param string $message
	 * @param EditableInterface $editable
	 * @param string $action 
	 */
	public function __construct($message, EditableInterface $editable, $action)
	{
		$class = get_class($editable);
		$message = "{$message} for {$editable} action '{$action}'";
		
		parent::__construct($message);
	}
}
