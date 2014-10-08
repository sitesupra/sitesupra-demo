<?php

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

/**
 * Set of page blocks
 * @method Block findById($id)
 */
class BlockSet extends AbstractSet
{
	/**
	 * Remove block marking as invalid
	 * @param Entity\Abstraction\Block $block
	 * @param string $reason 
	 */
	public function removeInvalidBlock(Block $block, $reason = 'unknown')
	{
		/* @var $testBlock Entity\Abstraction\Block */
		foreach ($this as $index => $testBlock) {
			if ($testBlock->equals($block)) {
				\Log::warn("Block {$block} removed as invalid with reason: {$reason}");
				
				$this->offsetUnset($index);
			}
		}
	}
	
	/**
	 * @return BlockSet
	 */
	public function getPlaceHolderBlockSet(PlaceHolder $placeHolder)
	{
		$blockSet = new BlockSet();
		
		/* @var $block Block */
		foreach ($this as $block) {
			if ($block->getPlaceHolder()->equals($placeHolder)) {
				$blockSet[] = $block;
			}
		}
		
		return $blockSet;
	}
	
	/**
	 * Will order the blocks by the placeholder position, leave the order for
	 * blocks inside the same placeholder.
	 * @param array $placeHolderNames
	 */
	public function orderByPlaceHolderNameArray(array $placeHolderNames = null)
	{
		if (is_null($placeHolderNames)) {
			return;
		}
		
		$blockArray = iterator_to_array($this);
		
		$sortFunction = function(Block $block1, Block $block2) use ($placeHolderNames, $blockArray) {
			$name1 = $block1->getPlaceHolder()->getName();
			$name2 = $block2->getPlaceHolder()->getName();
			
			$position1 = array_search($name1, $placeHolderNames, true);
			$position2 = array_search($name2, $placeHolderNames, true);
			
			// If in the same placeholder, leave the order as is
			if ($position1 === $position2) {
				$position1 = array_search($block1, $blockArray, true);
				$position2 = array_search($block2, $blockArray, true);
			}

			return $position1 - $position2;
		};
			
		$this->uasort($sortFunction);
	}
	
}
