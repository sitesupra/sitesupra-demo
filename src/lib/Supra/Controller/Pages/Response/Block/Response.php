<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\Http,
		Supra\Editable\EditableInterface,
		Supra\Controller\Pages\Entity\Abstraction\Block,
		Supra\Controller\Pages\Entity\BlockProperty;

/**
 * Response for block
 */
abstract class Response extends Http
{
	/**
	 * @var Block
	 */
	private $block;
	
	/**
	 * @return Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock($block)
	{
		$this->block = $block;
	}
	
	/**
	 * Get the content and output it to the response or return if requested
	 * 
	 * TODO: no editable mode for editables belonging to parent objects
	 * 
	 * @param BlockProperty $property
	 * @return string
	 */
	public function outputProperty(BlockProperty $property)
	{
		$data = $property->getValue();
		$editable = $property->getEditable();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		$this->output($filteredValue);
		
		return $filteredValue;
	}
}
