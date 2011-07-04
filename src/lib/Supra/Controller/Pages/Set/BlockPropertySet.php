<?php

namespace Supra\Controller\Pages\Set;

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
}
