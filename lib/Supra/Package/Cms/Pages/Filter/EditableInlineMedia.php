<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Controller\Pages\Entity\BlockProperty;

/**
 *
 */
class EditableInlineMedia extends InlineMediaFilter
{
	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * @param mixed $content
	 * @return \Twig_Markup
	 */
	public function filter($content)
	{
		$element = parent::filter($content);
		
		$propertyName = $this->property->getName();
			
		$block = $this->property->getBlock();
		$blockId = $block->getId();

		if ($element instanceof InlineMediaMarkup) {
			$element->addWrapper(array(
				'<div id="content_' . $blockId . '_' . $propertyName 
				. '" class="yui3-content-inline yui3-input-media-inline-content">',
				'</div>',
			));
			
			return $element;
			
		} else {
			
			return new \Twig_Markup('<div id="content_' . $blockId . '_' . $propertyName 
				. '" class="yui3-content-inline yui3-input-media-inline-content"></div>', 'UTF-8');
			
		}
	}
}
