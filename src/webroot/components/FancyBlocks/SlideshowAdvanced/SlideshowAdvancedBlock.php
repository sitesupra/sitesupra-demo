<?php

namespace Project\FancyBlocks\SlideshowAdvanced;

use Supra\Controller\Pages\BlockController;

class SlideshowAdvancedBlock extends BlockController
{
	protected function doExecute()
	{
		$slidesData = $this->prepareSlidesData();
		
		$this->getResponse()
				->assign('slides', $slidesData)
				->outputTemplate('index.html.twig');
	}
	
	protected function prepareSlidesData()
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
					'background' => $slide['background'],
					'layout' => $slide['layout'],
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

}
