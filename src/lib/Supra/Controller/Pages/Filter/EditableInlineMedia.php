<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity;
use Twig_Markup;

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
		$metadata = $this->property->getMetadata();
		$metaItem = $metadata->get(0);
		
		$filteredValue = null;
				
		if ($metaItem instanceof Entity\BlockPropertyMetadata) {
			$element = $metaItem->getReferencedElement();
	
			if ($element instanceof Entity\ReferencedElement\ReferencedElementAbstract) {

				if ($element instanceof VideoReferencedElement) {
					$filteredValue = $this->parseSupraVideo($element);
				}
				else if ($element instanceof Entity\ReferencedElement\ImageReferencedElement) {
					$filteredValue = $this->parseSupraImage($element);
				}
			}
		}
		
		$propertyName = $this->property->getName();
			
		$block = $this->property->getBlock();
		$blockId = $block->getId();

		$html = '<div id="content_' . $blockId . '_' . $propertyName 
				. '" class="yui3-content-inline yui3-input-media-inline-content">';
				
		$html .= $filteredValue;
		$html .= '</div>';
		
		$markup = new Twig_Markup($html, 'UTF-8');
		
		return $markup;
	}
}
