<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;

/**
 * Filters the value to enable Html editing for CMS
 */
class InlineTextareaFilter implements FilterInterface
{

	/**
	 * @var BlockProperty
	 */
	public $property;

	public function filter($content)
	{
		$propertyName = $this->property->getName();

		$block = $this->property->getBlock();
		$blockId = $block->getId();

		// Normalize block name
		$blockName = $block->getComponentName();

		$html = '<div id="content_' . $blockId . '_' . $propertyName
				. '" class="yui3-content-inline yui3-input-textarea-inline">';
		$html .= htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
		$html = strtr($html, array("\r\n" => '<br />', "\n" => '<br />'));
		$html .= '</div>';

		$markup = new \Twig_Markup($html, 'UTF-8');
		
		return $markup;
	}

}
