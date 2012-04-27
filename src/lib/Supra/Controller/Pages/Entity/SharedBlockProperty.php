<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Returned when page uses shared property from another page
 */
class SharedBlockProperty extends BlockProperty
{
	/**
	 * Block group ID the property is assigned to
	 * @var string
	 */
	private $groupId;
	
	public function __construct(BlockProperty $blockProperty, Abstraction\Block $block, Abstraction\Localization $localization, $groupId)
	{
		// FIXME: maybe some better way to copy the data?
		foreach ($blockProperty as $field => $value) {
			$this->$field = $value;
		}
		
		$this->block = $block;
		$this->localization = $localization;
		$this->groupId = $groupId;
	}
	
	public function getGroupId()
	{
		return $this->groupId;
	}
}
