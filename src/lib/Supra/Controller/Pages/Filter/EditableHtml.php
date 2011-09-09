<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;

/**
 * Filters the value to enable Html editing for CMS
 */
class EditableHtml extends ParsedHtmlFilter
{
	/**
	 * @var BlockProperty
	 */
	public $property;
	
	/**
	 * Filters the editable content's data, adds Html Div node for CMS
	 * @params string $content
	 * @return string
	 */
	public function filter($content)
	{
		$value = $this->property->getValue();
		$valueData = $this->property->getValueData();
		$content = $this->parseSupraMarkup($value, $valueData);
		
		$propertyName = $this->property->getName();
			
		$block = $this->property->getBlock();
		$blockId = $block->getId();

		// Normalize block name
		$blockName = $block->getComponentName();

		$html = '<div id="content_' . $blockName . '_' . $blockId . '_' . $propertyName 
				. '" class="yui3-content-inline yui3-input-html-inline-content">';
		$html .= $content;
		$html .= '</div>';
		
		return $html;
	}
}
