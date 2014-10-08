<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\ReferencedElement;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity;

/**
 *
 */
class InlineMediaFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * @param string $content
	 * @return \Twig_Markup
	 */
	public function filter($content)
	{
		$metadata = $this->property->getMetadata();
		$metaItem = $metadata->get(0);
		
		if ( ! $metaItem instanceof Entity\BlockPropertyMetadata) {
			return null;
		}
		
		$element = $metaItem->getReferencedElement();
		
		if ($element instanceof ReferencedElement\VideoReferencedElement
				|| $element instanceof ReferencedElement\ImageReferencedElement) {
			
			return new InlineMediaMarkup($element, $this);
		}
	}	
}