<?php

namespace Supra\Package\Cms\Entity;

/**
 * Returned when page uses shared property from another page
 */
//class SharedBlockProperty extends BlockProperty
//{
//	/**
//	 * Block group ID the property is assigned to
//	 * @var string
//	 */
//	private $groupId;
//
//	/**
//	 * @var Abstraction\Localization
//	 */
//	private $originalLocalization;
//
//	/**
//	 * @var BlockProperty
//	 */
//	private $replacedBlockProperty;
//
//
//	public function __construct(BlockProperty $blockProperty, Abstraction\Block $block, Abstraction\Localization $localization, $groupId)
//	{
//		// FIXME: maybe some better way to copy the data?
//		foreach ($blockProperty as $field => $value) {
//			$this->$field = $value;
//		}
//
//		$this->originalLocalization = $this->localization;
//
//		$this->block = $block;
//		$this->localization = $localization;
//		$this->groupId = $groupId;
//	}
//
//	public function getGroupId()
//	{
//		return $this->groupId;
//	}
//
//	/**
//	 * @return Abstraction\Localization
//	 */
//	public function getOriginalLocalization()
//	{
//		return $this->originalLocalization;
//	}
//
//	/**
//	 * @return BlockProperty
//	 */
//	public function getReplacedBlockProperty()
//	{
//		return $this->replacedBlockProperty;
//	}
//
//	/**
//	 * @param BlockProperty $blockProperty
//	 */
//	public function setReplacedBlockProperty($blockProperty)
//	{
//		$this->replacedBlockProperty = $blockProperty;
//	}
//}
