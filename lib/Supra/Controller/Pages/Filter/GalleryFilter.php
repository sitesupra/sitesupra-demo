<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;

/**
 * Collects gallery information
 */
class GalleryFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * @param string $content irrelevant here
	 */
	public function filter($content)
	{
		$metadata = $this->property->getMetadata();
		$images = array();
		
		foreach ($metadata as $metadataItem) {
			/* @var $metadataItem BlockPropertyMetadata */
			$referencedElement = $metadataItem->getReferencedElement();
			
			if ($referencedElement instanceof ImageReferencedElement) {
				$images[] = $referencedElement;
			}
		}
		
		return $images;
	}
}
