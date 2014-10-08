<?php

namespace Supra\Package\Cms\Editable\Exception;

use Supra\Editable\EditableInterface;

/**
 * Exception on filter not defined or found issues
 */
class FilterNotFound extends RuntimeException
{
	/**
	 * Generates the message automatically
	 * @param string $message
	 * @param EditableInterface $editable
	 */
	public function __construct($message, EditableInterface $editable)
	{
		$class = get_class($editable);
		$message = "{$message} for {$editable}";
		
		parent::__construct($message);
	}
}
