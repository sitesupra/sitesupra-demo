<?php

namespace Supra\Editable\Filter;

use Supra\Editable\EditableInterface;

/**
 * Filter with escaped HTML
 */
class EscapeHtml implements FilterInterface
{
	/**
	 * Filters the editable content's data
	 * @params EditableInterface $editable
	 * @return string
	 */
	public function filter(EditableInterface $editable)
	{
		$content = $editable->getContent();
		$content = htmlspecialchars($content);
		
		return $content;
	}

}
