<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\Http;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\Entity;

/**
 * Response for block
 */
abstract class Response extends Http
{
	/**
	 * @var Entity\Abstraction\Block
	 */
	private $block;
	
	/**
	 * @return Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock(Entity\Abstraction\Block $block)
	{
		$this->block = $block;
	}
	
	/**
	 * Get the content and output it to the response or return if requested
	 * 
	 * TODO: no editable mode for editables belonging to parent objects
	 * 
	 * @param Entity\BlockProperty $property
	 * @return string
	 */
	public function outputProperty(Entity\BlockProperty $property)
	{
		$data = $property->getValue();
		$editable = $property->getEditable();
		$filteredValue = $editable->getFilteredValue(static::EDITABLE_FILTER_ACTION);
		$this->output($filteredValue);
		
		return $filteredValue;
	}
}
