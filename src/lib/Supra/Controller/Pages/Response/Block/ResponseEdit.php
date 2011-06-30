<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\ResponseInterface,
		Supra\Editable\EditableAbstraction;

/**
 * Response for block in edit mode
 */
class ResponseEdit extends Response
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
		//TODO: move to the block method
		$blockName = $block->getComponent();
		$blockName = trim($blockName, '\\');
		$blockName = str_replace('\\', '_', $blockName);
		
		// TEMP
//		$blockName = 'html';
		
		$response->output('<div id="content_' . $blockName . '_' . $blockId
				. '" class="yui3-page-content yui3-page-content-' . $blockName 
				. ' yui3-page-content-' . $blockName . '-' . $blockId . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
	
	/**
	 * @param BlockProperty $property
	 * @return string
	 */
	public function outputProperty(BlockProperty $property)
	{
		$data = $property->getValue();
		$editable = $property->getEditable();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		
		//TODO: should be customizable somewhere
		if ($property->getEditable() instanceof \Supra\Editable\Html) {
			
			$propertyName = $property->getName();
			
			$block = $property->getBlock();
			$blockId = $block->getId();
			
			// Normalize block name
			//TODO: move to the block method
			$blockName = $block->getComponent();
			$blockName = trim($blockName, '\\');
			$blockName = str_replace('\\', '_', $blockName);
			
			// TEMP
//			$blockName = 'html';
			
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
