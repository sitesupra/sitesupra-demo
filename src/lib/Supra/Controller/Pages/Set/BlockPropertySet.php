<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Entity;

/**
 * Set of page block properties
 */
class BlockPropertySet extends AbstractSet
{
	/**
	 * @TODO: maybe optimize by grouping the properties once
	 * @param Entity\Abstraction\Block $block
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet(Entity\Abstraction\Block $block)
	{
		$blockPropertySet = new BlockPropertySet();
		
		/* @var $blockProperty Entity\BlockProperty */
		foreach ($this as $blockProperty) {
			if ($blockProperty->getBlock()->equals($block)) {
				$blockPropertyName = $blockProperty->getName();
				$blockPropertySet->offsetSet($blockPropertyName, $blockProperty);
			}
		}
		
		return $blockPropertySet;
	}
	
	/**
	 * Get distinct block ID list
	 * @return array
	 */
	public function getBlockIdList()
	{
		$blockIdList = array();
		
		foreach ($this as $property) {
			$blockId = $property->getBlock()->getId();

			// The problematic case when block is part of parent templates
			$blockIdList[$blockId] = $blockId;
		}
		
		$blockIdList = array_values($blockIdList);
		
		return $blockIdList;
	}
}
