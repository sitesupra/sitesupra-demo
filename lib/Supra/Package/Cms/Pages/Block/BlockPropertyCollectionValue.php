<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Pages\BlockController;

class BlockPropertyCollectionValue implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * @var BlockProperty
	 */
	private $collectionProperty;

	/**
	 * @var BlockController
	 */
	private $controller;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array
	 */
	private $values;

	/**
	 * @param BlockProperty $collectionProperty
	 * @param BlockController $controller
	 * @param array $options
	 */
	public function __construct(
			BlockProperty $collectionProperty,
			BlockController $controller,
			array $options
	) {
		$this->collectionProperty = $collectionProperty;
		$this->controller = $controller;
		$this->options = $options;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if (! $this->offsetExists($name)) {
			throw new \RuntimeException("Property [{$name}] is missing.");
		}

		return $this->controller->getPropertyViewValue(
				$this->collectionProperty->getName() . '.' . $name
		);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset)
	{
		return $this->collectionProperty
				->getProperties()
				->offsetExists($offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet($offset)
	{
		return $this->getPropertyValue($offset);
	}

	/**
	 * @throws \BadMethodCallException
	 */
	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException('Collection is read only.');
	}

	/**
	 * @throws \BadMethodCallException
	 */
	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException('Collection is read only.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->collectionProperty
				->getProperties()
				->count();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->getAllValues());
	}

	/**
	 * @return array
	 */
	private function getAllValues()
	{
		if ($this->values === null) {

			$this->values = array();

			foreach ($this->collectionProperty as $property) {

				$value = $this->controller->getPropertyViewValue(
						$property->getHierarchicalName(),
						$this->options
				);

				if ($value !== null) {
					$this->values[$property->getName()] = $value;
				}
			}
		}

		return $this->values;
	}
}