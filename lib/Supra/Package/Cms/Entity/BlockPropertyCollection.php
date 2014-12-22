<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections;

/**
 * @Entity
 */
class BlockPropertyCollection extends BlockProperty implements \IteratorAggregate, \Countable, \ArrayAccess
{
	/**
	 * @OneToMany(targetEntity="BlockProperty", mappedBy="collection")
	 * @var Collections\Collection
	 */
	protected $properties;

	/**
	 * {@inheritDoc}
	 */
	public function __construct($name)
	{
		parent::__construct($name);
		
		$this->properties = new Collections\ArrayCollection();
	}

	/**
	 * @param BlockProperty $property
	 */
	public function addProperty($property)
	{
		$property->setCollection($this);
		$this->properties->add($property);
	}

	/**
	 * @return Collections\Collection
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return BlockProperty
	 * @throws \RuntimeException
	 */
	public function getProperty($name)
	{
		if (! $this->properties->offsetExists($name)) {
			throw new \RuntimeException("Collection [{$this->name}] has no property [{$name}].");
		}

		return $this->properties->offsetGet($name);
	}

	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->properties->count();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->properties->getIterator());
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset)
	{
		return $this->properties->offsetExists($offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet($offset)
	{
		return $this->getProperty($offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException('Use addProperty() instead.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset)
	{
		$this->properties->offsetUnset($offset);
	}
}