<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Entity;

/**
 * Set of page blocks
 */
class BlockSet extends AbstractSet
{
	/**
	 * Remove block marking as invalid
	 * @param Entity\Abstraction\Block $block
	 * @param string $reason 
	 */
	public function removeInvalidBlock(Entity\Abstraction\Block $block, $reason = 'unknown')
	{
		/* @var $testBlock Entity\Abstraction\Block */
		foreach ($this as $index => $testBlock) {
			if ($testBlock->equals($block)) {
				\Log::swarn("Block {$block} removed as invalid with reason: {$reason}");
				
				$this->offsetUnset($index);
			}
		}
	}
	
	/**
	 * @return BlockSet
	 */
	public function getPlaceHolderBlockSet(Entity\Abstraction\PlaceHolder $placeHolder)
	{
		$blockSet = new BlockSet();
		
		/* @var $block Entity\Abstraction\Block */
		foreach ($this as $block) {
			if ($block->getPlaceHolder()->equals($placeHolder)) {
				$blockSet[] = $block;
			}
		}
		
		return $blockSet;
	}
	
}
