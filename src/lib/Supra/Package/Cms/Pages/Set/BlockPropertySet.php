<?php

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity;

/**
 * Set of page block properties
 */
class BlockPropertySet extends AbstractSet
{
	/**
	 * @TODO: maybe optimize by grouping the properties once
	 * @param Block $block
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet(Block $block)
	{
		$blockPropertySet = new BlockPropertySet();
		
		/* @var $blockProperty Entity\BlockProperty */
		foreach ($this as $blockProperty) {
			if ($blockProperty->getBlock()->equals($block)) {
				$blockPropertySet->append($blockProperty);
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
	
	/**
	 * Gets only properties referencing to the current page, properties from the
	 * locked parent template blocks are ignored
	 * @param Entity\Abstraction\Localization $data
	 */
	public function getPageProperties(Entity\Abstraction\Localization $data)
	{
		$blockPropertySet = new BlockPropertySet();
		
		/* @var $blockProperty Entity\BlockProperty */
		foreach ($this as $blockProperty) {

			if ($blockProperty->getOriginalLocalization()->equals($data)) {
				$blockPropertySet->append($blockProperty);
			}
		}
		
		return $blockPropertySet;
	}
	
//	/**
//	 * Add overriden metadata for all properties with the ID provided
//	 * @param string $propertyId
//	 * @param Entity\BlockPropertyMetadata $propertyMetadata
//	 */
//	public function addOverridenMetadata($propertyId, Entity\BlockPropertyMetadata $propertyMetadata)
//	{
//		foreach ($this as $property) {
//			if ($property->getId() === $propertyId) {
//				$property->addOverridenMetadata($propertyMetadata);
//			}
//		}
//	}
	
	/**
	 * Gets only properties (subproperties) referencing to the specified metadata
	 * @param Entity\BlockPropertyMetadata $metadata
	 * @return BlockPropertySet
	 */
	public function getMetadataProperties(Entity\BlockPropertyMetadata $metadata) {
		
		$blockPropertySet = new BlockPropertySet();
		
		$metadataId = $metadata->getId();
		
		/* @var $blockProperty Entity\BlockProperty */
		foreach($this as $blockProperty) {
			if ($blockProperty->getMasterMetadataId() === $metadataId) {
				$blockPropertySet->append($blockProperty);
			}
		}
		
		return $blockPropertySet;
	}
}
