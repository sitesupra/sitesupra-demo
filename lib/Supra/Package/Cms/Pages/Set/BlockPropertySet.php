<?php

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Block;

/**
 * Set of page block properties
 */
class BlockPropertySet extends AbstractSet
{
	/**
	 * @TODO: maybe optimize by grouping the properties once
	 *
	 * @param Block $block
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet(Block $block)
	{
		$blockPropertySet = new BlockPropertySet();
		
		/* @var $blockProperty \Supra\Package\Cms\Entity\BlockProperty */
		foreach ($this as $blockProperty) {
			if ($blockProperty->getBlock()->equals($block)) {
				$blockPropertySet->append($blockProperty);
			}
		}
		
		return $blockPropertySet;
	}
}
