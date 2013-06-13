<?php

namespace Project\FancyBlocks\SlideshowAdvanced;

use Supra\Controller\Pages\BlockController,
	Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;

class SlideshowAdvancedBlock extends BlockController
{
	/**
	 * 
	 */
	protected function doExecute()
	{
		$slides = $this->prepareSlides();
		
		$this->getResponse()
				->assign('slides', $slides)
				->outputTemplate('index.html.twig');
	}
	
	/**
	 * @return array
	 */
	protected function prepareSlides()
	{
		$slidesResponse = array();
		
		
		$slidesArray = $this->getPropertyValue('slides');
		
		$layouts = $this->getAvailableLayouts();
		
		if ( ! empty($slidesArray)) {
			
			foreach ($slidesArray as $slide) {

                if ($slide[active] == 'true') {

                    $startDate = strtotime($slide[period_from]);
                    $endDate = strtotime($slide[period_to]);
                    $now = strtotime(date('Y-m-d H:i'));

                    if ((!$startDate || $startDate <= $now) && (!$endDate || $now <= $endDate))
                    {
        				if ( ! isset($layouts[$slide['layout']])) {
        					continue;
        				}
        				
        				$slide = $slide + array(
        				    'theme' => null,
        				    'mask_image' => null,
        				    'mask_color' => null,
        					'text_main' => null,
        					'text_top' => null,
        					'media' => null,
        					'mediaType' => null,
        					'image' => null,
        					'background' => null,
        					'background_color' => null,
        					'layout' => null,
        					'buttons' => null,
        					'height' => null,
        				);
        				
        				$slideData = array(
        					'theme' => $slide['theme'],
        					'mask_image' => '/resources/img/sample/slideshow-mask.png',
                            'mask_color' => null,
        					'text_main' => $this->filterHtml($slide['text_main']),
        					'text_top' => $this->filterHtml($slide['text_top']),
        					'media' => $this->filterMedia($slide['media']),
        					'mediaType' => $this->getMediaType($slide['media']),
        					'image' => $slide['image'],
        					'background' => $this->filterBackground($slide['background']),
                            'background_color' => $slide['background_color'],
        					'layout' => $slide['layout'],
        					'buttons' => $this->filterButtons($slide['buttons']),
        					'height' => $slide['height'],
        				);
        				
        				$slidesResponse[] = $slideData;
        			}
        		}
			}
		}
		
		return $slidesResponse;
	}
	
	/**
	 * @FIXME: this is wrooong
	 */
	private function getAvailableLayouts()
	{
		$configuration = $this->getConfiguration();
		$layouts = null;
		foreach ($configuration->properties as $propertyConfiguration) {
			if ($propertyConfiguration->editableInstance instanceof \Supra\Editable\Slideshow) {
				$layouts = $propertyConfiguration->values;
				break;
			}
		}
		
		$layoutsArray = array();
		foreach ($layouts as $layout) {
			$layoutsArray[$layout['id']] = $layout['fileName'];
		}
		
		return $layoutsArray;
	}
	
	/**
	 * @param array $htmlData
	 * @return string
	 */
	private function filterHtml($htmlData)
	{
		$html = $htmlData['html'];
		$data = (isset($htmlData['data']) ? $htmlData['data'] : array());
		
		$elements = array();
		foreach ($data as $key => $referencedElementData) {
			
			$element = \Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract::fromArray($referencedElementData);
			
			if ($element instanceof ImageReferencedElement) {
				$this->getImageElementSizeName($element);
			}
			
			$elements[$key] = $element;
		}
			
		$filter = new \Supra\Controller\Pages\Filter\ParsedHtmlFilter();
		$filteredHtml = $filter->doFilter($html, $elements);
		
		return $filteredHtml;
	}
	
	/**
	 * @param array $buttonsData
	 * @return array
	 */
	private function filterButtons($buttonsData)
	{
		if ( ! empty($buttonsData)) {
			foreach ($buttonsData as &$button) {
				if (is_array($button['link'])) {
					
					$linkElement = new \Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
					$linkElement->fillArray($button['link']);
					
					$button['target'] = $linkElement->getTarget();
					\Log::error($button['target']);
					$button['link'] = $linkElement;
				}
			}
		}
		
		return $buttonsData;
	}
	
	/**
	 * @param array $backgroundData
	 * @return array|null
	 */
	private function filterBackground($backgroundData)
	{		
		if ( ! empty($backgroundData) && isset($backgroundData['image'])) {
			
			$element = new ImageReferencedElement;
			$element->fillArray($backgroundData['image']);
			
			$sizeName = $this->getImageElementSizeName($element);
			
			$backgroundData['image']['size_name'] = $sizeName;
			
			return $backgroundData['image'];
		}
		
		return null;
	}
	
	private function filterMedia($mediaData)
	{
		if (isset($mediaData['type']) && $mediaData['type'] == 'image') {
			
			$element = new ImageReferencedElement;
			$element->fillArray($mediaData);
			
			$sizeName = $this->getImageElementSizeName($element);
			
			$mediaData['size_name'] = $sizeName;
		}
		
		return $mediaData;
	}
	
	private function getMediaType($mediaData)
    {
        if (isset($mediaData['type'])) {
            return 'type-' . $mediaData['type'];
        }
        
        return null;
    }
	
	/**
	 * @TODO: performance?
	 * @param \Project\FancyBlocks\SlideshowAdvanced\ImageReferencedElement $element
	 * @return string
	 */
	protected function getImageElementSizeName(ImageReferencedElement $element)
	{
		$imageId = $element->getImageId();
		$width = $element->getWidth();
		$height = $element->getHeight();

		$fileStorage = $this->getFileStorage();

		$fsEm = $fileStorage->getDoctrineEntityManager();
		$image = $fsEm->find(\Supra\FileStorage\Entity\Image::CN(), $imageId);

		if ( ! $image instanceof \Supra\FileStorage\Entity\Image) {
			\Log::warn("Image by ID $imageId was not found inside the file storage specified." .
					" Maybe another file storage must be configured for the image size creator listener?");

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
		
		return $sizeName;
	}
	
	private function getFileStorage()
	{
		if (is_null($this->fileStorage)) {
			$this->fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
		}
		
		return $this->fileStorage;
	}

}
