<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockProperty;

abstract class AbstractProperty
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var AbstractProperty
	 */
	protected $parent;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		if (strpos($name, '.') !== false) {
			throw new \InvalidArgumentException('Dots are not allowed.');
		}

		$this->name = $name;
	}

	/**
	 * @param AbstractProperty $parent
	 */
	public function setParent(AbstractProperty $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return bool
	 */
	public function hasParent()
	{
		return $this->parent !== null;
	}

	/**
	 * @return AbstractProperty
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @return string
	 */
	public function getHierarchicalName()
	{
		if ($this->parent === null) {
			return $this->name;
		}

		return $this->parent->getHierarchicalName() . '.' . $this->name;
	}

	/**
	 * @return BlockProperty
	 */
	abstract public function createBlockProperty($name);

	/**
	 * @param BlockProperty $property
	 * @return bool
	 */
	abstract public function isMatchingProperty(BlockProperty $property);
}