<?php

namespace Supra\Editable\Filter;

use Supra\Editable\EditableInterface;

/**
 * Raw editable content filter without filter
 */
class Raw implements FilterInterface
{
	/**
	 * Filters the editable content's data
	 * @params EditableInterface $editable
	 * @return string
	 */
	public function filter(EditableInterface $editable)
	{
		return $editable->getContent();
	}
}
