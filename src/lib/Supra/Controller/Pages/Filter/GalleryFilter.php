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
		
		$dummyGalleryController = new \Supra\Controller\Pages\GalleryBlockController();
		
		foreach ($metadata as $metadataItem) {
			/* @var $metadataItem BlockPropertyMetadata */
			$referencedElement = $metadataItem->getReferencedElement();
			
			if ($referencedElement instanceof ImageReferencedElement) {

				$dummyGalleryController->setParentMetadata($metadataItem);
				$properties = $metadataItem->getMetadataProperties();
				
				$propertyValues = array();
				foreach($properties as $property) {
					$propertyName = $property->getName();
					try {
						$propertyValues[$propertyName] = $dummyGalleryController->getPropertyValue($propertyName);
					} catch (\Supra\Controller\Pages\Exception\RuntimeException $e) {
						
						continue;
					}
				}
				
				$images[] = $propertyValues + $referencedElement->toArray();
			}
		}
		
		return $images;
	}
}
