<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;

use Supra\Controller\Pages\GalleryBlockController;
use Supra\Controller\Pages\Exception\RuntimeException;
use Supra\Controller\Pages\Entity\ReferencedElement\IconReferencedElement;

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
			
			if ($referencedElement instanceof ImageReferencedElement
					|| $referencedElement instanceof IconReferencedElement) {

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
				$element = $referencedElement->toArray();
				
				if ($referencedElement instanceof IconReferencedElement) {
					
					$tag = null;
					
					$svgContent = $referencedElement->getIconSvgContent();
				
					if ( ! empty($svgContent)) {

						$tag = new \Supra\Html\HtmlTag('svg');
						$style = '';

						$tag->setContent($svgContent);

						$tag->setAttribute('version', '1.1');
						$tag->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
						$tag->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
						$tag->setAttribute('x', '0px');
						$tag->setAttribute('y', '0px');
						$tag->setAttribute('viewBox', '0 0 512 512', true);
						$tag->setAttribute('enable-background', 'new 0 0 512 512');
						$tag->setAttribute('xml:space', 'preserve');

						$color = $referencedElement->getColor();
						if ( ! empty($color)) {
							$style = "fill: {$color};";
						}

						$align = $referencedElement->getAlign();
						if ( ! empty($align)) {
							$tag->addClass('align-' . $align);
						}

						$width = $referencedElement->getWidth();
						if ( ! empty($width)) {
							$tag->setAttribute('width', $width);
                            $style .= "width: {$width}px;";
						}

						$height = $referencedElement->getHeight();
						if ( ! empty($height)) {
							$tag->setAttribute('height', $height);
                              $style .= "height: {$height}px;";
						}
						
						if ( ! empty($style)) {
                            $tag->setAttribute('style', $style);
						}
					}
					
					$element['tag'] = $tag;
					
				}
				
				// Subarray with properties
				$element['property'] = $propertyValues;
				$images[] = $element;
			}
		}
		
		return $images;
	}
}
