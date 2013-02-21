<?php

namespace Project\FancyBlocks\SlideshowAdvanced;

use Supra\Controller\Pages\BlockController;

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
				
				if ( ! isset($layouts[$slide['layout']])) {
					continue;
				}
				
				$slideData = array(
					'text_main' => $this->filterHtml($slide['text_main']),
					'text_top' => $this->filterHtml($slide['text_top']),
					'media' => $slide['media'],
					'image' => $slide['image'],
					'background' => $this->filterBackground($slide['background']),
					'layout' => $slide['layout'],
					'buttons' => $this->filterButtons($slide['buttons']),
				);
				
				$slidesResponse[] = $slideData;
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
			$elements[$key] = \Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract::fromArray($referencedElementData);
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
			return $backgroundData['image'];
		}
		
		return null;
	}

}
