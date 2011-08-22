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
		//FIXME: Filter moved to the block response because objects are not accessible here
		return $editable->getContent();
		
//		//TODO: hardcoded
//		static $ids = array('5', '5', '7', '5', '6', '7', '5');
//		$id = array_shift($ids);
//		
//		$content = $editable->getContent();
//		
//		// Hardcoded
//		$blockName = 'html';
//		$propertyName = 'html';
//		
//		$html = '<div id="content_' . $blockName . '_' . $id . '_' . $propertyName . '" class="yui3-page-content yui3-page-content-html yui3-page-content-html-' . $id . '">';
//		$html .= $content;
//		$html .= '</div>';
//		
//		return $html;
	}
}
