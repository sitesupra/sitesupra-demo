<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Editable;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract;

/**
 *
 */
class MediaGalleryFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;
	
	/**
	 */
	private $fileStorage;
	
	/**
	 */
	public function filter($content)
	{
		$block = $this->property->getBlock();
		$blockController = $block->createController();
		
		$configuration = $blockController->getConfiguration();
		$propertyConfiguration = $configuration->getProperty($this->property->getName('name'));
		
		$editable = $propertyConfiguration->editableInstance;
		/* @var $editable \Supra\Editable\MediaGallery */
		if ( ! $editable instanceof \Supra\Editable\MediaGallery) {
			// how this can be?!
			throw new \Exception('Wrong editable is received inside MediaGalleryFilter');
		}
		
//		$serializedPropertyConfigs = $editable->getItemPropertyConfigurations();
		
//		$value = $this->property->getValue();
//		$itemsData = unserialize($value);
	
//		if ($itemsData === false) {
//			return null;
//		}
		
		$filteredContent = array();
		
		if ( ! empty($content )) {
			foreach ($content as $key => $itemData) {

				$values = array();
				foreach ($propertyConfiguration->properties as $config) {

					$name = $config->name;
					$filteredValue = null;

					if (isset($itemData[$name])) {

						if ($config->editableInstance instanceof Editable\Html) {

							$filteredValue = $this->getFilteredHtmlValue($itemData[$name]);
						}
						else if ($config->editableInstance instanceof Editable\InlineMedia) {

							$filteredValue = $this->getFilteredInlineMediaValue($itemData[$name]);
						}
						else {				
							$propertyEditable = clone $config->editableInstance;
							$propertyEditable->setContent($itemData[$name]);

							$filteredValue = $propertyEditable->getFilteredValue();
						}

						$values[$name] = $filteredValue;
					}
				}

				$filteredContent[$key] = $values;
			}
		}
		
		return $filteredContent;
	}
	
	
	/**
	 */
	protected function getFilteredInlineMediaValue($mediaData)
	{
		if ( ! empty($mediaData)) {
			if (isset($mediaData['type']) && $mediaData['type'] == 'image') {

				$element = new ImageReferencedElement;
				$element->fillArray($mediaData);

				$this->populateImageElementWithSize($element);
				
				return $element->toArray();
			} else {
				
				return $mediaData;
			}
		}
		
		return null;
	}
	
	/**
	 */
	protected function getFilteredHtmlValue($htmlData)
	{
		$html = (isset($htmlData['html']) ? $htmlData['html'] : $htmlData);
		$data = (isset($htmlData['data']) ? $htmlData['data'] : array());

		$elements = array();
		
		foreach ($data as $key => $elementData) {

			$element = ReferencedElementAbstract::fromArray($elementData);

			if ($element instanceof ImageReferencedElement) {
				$this->populateImageElementWithSize($element);
			}

			$elements[$key] = $element;
		}

		$filter = new \Supra\Package\Cms\Pages\Filter\ParsedHtmlFilter();
		$filteredHtml = $filter->doFilter($html, $elements);

		return $filteredHtml;
	}
	
	/**
	 */
	protected function populateImageElementWithSize(ImageReferencedElement $element)
	{
		$imageId = $element->getImageId();
		$width = $element->getWidth();
		$height = $element->getHeight();

		$fileStorage = $this->getFileStorage();

		$fsEm = $fileStorage->getDoctrineEntityManager();
		$image = $fsEm->find(\Supra\FileStorage\Entity\Image::CN(), $imageId);

		if ( ! $image instanceof \Supra\FileStorage\Entity\Image) {

			return;
		}

		// No dimensions
		if ($width > 0 && $height > 0 || $element->isCropped()) {

			if ($element->isCropped()) {
				$sizeName = $fileStorage->createImageVariant($image, $width, $height, $element->getCropLeft(), $element->getCropTop(), $element->getCropWidth(), $element->getCropHeight());
			} else {
				$sizeName = $fileStorage->createResizedImage($image, $width, $height);
			}
			$element->setSizeName($sizeName);
		}
	}
	
	/**
	 */
	private function getFileStorage()
	{
		if (is_null($this->fileStorage)) {
			$this->fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
		}
		
		return $this->fileStorage;
	}	
}
