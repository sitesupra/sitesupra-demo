<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Controller\Pages\Entity\BlockProperty;
use Twig_Markup;

/**
 * Filters the value to enable Html editing for CMS
 */
class EditableHtml extends ParsedHtmlFilter
{
	/**
	 * @var BlockProperty
	 */
	public $property;
	
	public function __construct()
	{
		$this->requestType = parent::REQUEST_TYPE_EDIT;
		parent::__construct();
	}
	
	/**
	 * Filters the editable content's data, adds Html Div node for CMS
	 * @params string $content
	 * @return string
	 */
	public function filter($content)
	{		
		$metadata = $this->property->getMetadata();
		
		$elements = array();
		foreach ($metadata as $key => $metadataItem) {
			$elements[$key] = $metadataItem->getReferencedElement();
		}
		
		$htmlContent = $this->parseSupraMarkup($content['html'], $elements);

		$propertyName = $this->property->getName();
			
		$block = $this->property->getBlock();
		$blockId = $block->getId();

		$html = '<div id="content_' . $blockId . '_' . $propertyName 
				. '" class="yui3-content-inline yui3-input-html-inline-content">';
		$html .= $htmlContent;
		$html .= '</div>';
		
		$markup = new Twig_Markup($html, 'UTF-8');
		
		return $markup;
	}
}
