<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Entity\BlockProperty;

class Property extends AbstractProperty
{
	/**
	 * @var Editable
	 */
	protected $editable;

	/**
	 * @param string $name
	 * @param Editable $editable
	 */
	public function __construct($name, Editable $editable)
	{
		parent::__construct($name);
		$this->editable = $editable;
	}

	/**
	 * @return Editable
	 */
	public function getEditable()
	{
		return $this->editable;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createBlockProperty($name)
	{
		$property = new BlockProperty($name);
		$property->setEditableClass(get_class($this->editable));

		return $property;
	}

	/**
	 * {@inhertitDoc}
	 */
	public function isMatchingProperty(BlockProperty $property)
	{
		if ($this->parent instanceof PropertyCollection) {

			$name = $property->getHierarchicalName();
			$name2 = $this->parent->getHierarchicalName() . '.' . $property->getName();

			return $property->getHierarchicalName() === $this->parent->getHierarchicalName() . '.' . $property->getName()
					&& $property->getEditableClass() === get_class($this->editable);
		}

		return $property->getHierarchicalName() === $this->getHierarchicalName()
				&& $property->getEditableClass() === get_class($this->editable);
	}
}