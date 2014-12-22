<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Entity\BlockPropertyCollection;

class BlockPropertyCollectionValue implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * @var BlockPropertyCollection 
	 */
	private $collection;

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
	 * @param BlockPropertyCollection $collection
	 * @param BlockController $controller
	 * @param array $options
	 */
	public function __construct(
			BlockPropertyCollection $collection,
			BlockController $controller,
			array $options
	) {
		$this->collection = $collection;
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
				$this->collection->getName() . '.' . $name
		);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset)
	{
		return $this->collection->offsetExists($offset);
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
		return $this->collection->count();
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

			foreach ($this->collection as $property) {

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