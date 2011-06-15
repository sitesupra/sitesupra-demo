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
		$content = $editable->getContent();
		
		$html = '<div id="content_html_111" class="yui3-page-content yui3-page-content-html yui3-page-content-html-111">';
		$html .= $content;
		$html .= '</div>';
		
		return $html;
	}
}
