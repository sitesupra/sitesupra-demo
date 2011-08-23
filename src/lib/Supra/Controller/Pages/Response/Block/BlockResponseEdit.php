<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\ResponseInterface;
use Supra\Editable\EditableAbstraction;
use Supra\Controller\Pages\Entity;

/**
 * Response for block in edit mode
 */
class BlockResponseEdit extends BlockResponse
{
	/**
	 * Editable filter action
	 * @var string
	 */
	const EDITABLE_FILTER_ACTION = EditableAbstraction::ACTION_EDIT;
	
	/**
	 * Flush the response to another response object
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$block = $this->getBlock();
		$blockId = $block->getId();
		
		// Normalize block name
		$blockName = $block->getComponentName();
		
		$response->output('<div id="content_' . $blockName . '_' . $blockId
				. '" class="yui3-page-content yui3-page-content-' . $blockName 
				. ' yui3-page-content-' . $blockName . '-' . $blockId . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
	
	/**
	 * @param Entity\BlockProperty $property
	 * @return string
	 */
	public function outputProperty(Entity\BlockProperty $property)
	{
		$valueData = $property->getValueData();
		$editable = $property->getEditable();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		
		//TODO: should be customizable somewhere
		if ($property->getEditable() instanceof \Supra\Editable\Html) {
			
			// CMS shouldn't need parsed HTML
			$filteredValue = $this->parseSupraMarkup($filteredValue, $valueData);
			
			$propertyName = $property->getName();
			
			$block = $property->getBlock();
			$blockId = $block->getId();
			
			// Normalize block name
			$blockName = $block->getComponentName();
			
			$html = '<div id="content_' . $blockName . '_' . $blockId . '_' . $propertyName 
					. '" class="yui3-page-content-inline yui3-input-html-inline-content">';
			$html .= $filteredValue;
			$html .= '</div>';
			
			$filteredValue = $html;
		}
		
		$this->output($filteredValue);
		
		return $filteredValue;
	}
}
