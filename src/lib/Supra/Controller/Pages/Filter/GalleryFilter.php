<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;

use Supra\Controller\Pages\GalleryBlockController;
use Supra\Controller\Pages\Exception\RuntimeException;

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
	 * @var PageRequest
	 */
	public $request;

	/**
	 * @param string $content irrelevant here
	 */
	public function filter($content)
	{
		$metadata = $this->property->getMetadata();
		$images = array();
		
		// dummy controller to fetch subproperties
		$galleryController = new GalleryBlockController();
		$pageData = $this->request->getPageLocalization();
		
		$this->request->setPageLocalization($pageData);
		
		$galleryController->setRequest($this->request);
		
		foreach ($metadata as $metadataItem) {
			/* @var $metadataItem BlockPropertyMetadata */
			$referencedElement = $metadataItem->getReferencedElement();
			
			if ($referencedElement instanceof ImageReferencedElement) {

				$galleryController->setParentMetadata($metadataItem);
				
				$properties = $galleryController->getMetadataProperties();
				
				$propertyValues = array();
				foreach($properties as $property) {
					$propertyName = $property->getName();
					try {
						$propertyValues[$propertyName] = $galleryController->getPropertyValue($propertyName);
					} catch (RuntimeException $e) {
						
						continue;
					}
				}
				
				// This might go for removal in future...
				$image = $referencedElement->toArray() + $propertyValues;
				
				// Subarray with properties
				$image['property'] = $propertyValues;
				$images[] = $image;
			}
		}
		
		return $images;
	}
}
