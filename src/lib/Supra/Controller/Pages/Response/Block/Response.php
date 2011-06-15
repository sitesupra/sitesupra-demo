<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\Http,
		Supra\Editable\EditableInterface;

/**
 * Response for block
 */
abstract class Response extends Http
{
	/**
	 * Get the content and output it to the response or return if requested
	 * @param EditableInterface $editable
	 * @return string
	 */
	public function outputEditable(EditableInterface $editable)
	{
		$data = $editable->getContent();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		$this->output($filteredValue);
		
		return $filteredValue;
	}
}
