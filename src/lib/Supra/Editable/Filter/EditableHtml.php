<?php

namespace Supra\Editable\Filter;

use Supra\Editable\EditableInterface;

/**
 * Filters the value to enable Html editing for CMS
 */
class EditableHtml implements FilterInterface
{
	/**
	 * Filters the editable content's data, adds Html Div node for CMS
	 * @params EditableInterface $editable
	 * @return string
	 */
	public function filter(EditableInterface $editable)
	{
		//TODO: hardcoded
		static $ids = array('111', '222', '333', '444', '555', '666', '777');
		$id = array_shift($ids);
		
		$content = $editable->getContent();
		
		$html = '<div id="content_html_' . $id . '" class="yui3-page-content yui3-page-content-html yui3-page-content-html-' . $id . '">';
		$html .= $content;
		$html .= '</div>';
		
		return $html;
	}
}
