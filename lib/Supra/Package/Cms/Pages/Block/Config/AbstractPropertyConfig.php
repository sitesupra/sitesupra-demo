<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Entity\BlockProperty;

abstract class AbstractPropertyConfig
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var AbstractPropertyConfig
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
	 * @param AbstractPropertyConfig $parent
	 */
	public function setParent(AbstractPropertyConfig $parent)
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
	 * @return AbstractPropertyConfig
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
	 * @param BlockProperty $property
	 * @return bool
	 */
	abstract public function isMatchingProperty(BlockProperty $property);

	/**
	 * @param string $name
	 * @return BlockProperty
	 */
	abstract public function createProperty($name);

	/**
	 * @return Editable
	 */
	abstract public function getEditable();
}