<?php

namespace Supra\Controller\Pages\Set;

use ArrayObject;
use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Exception;

/**
 * Set containing entity objects
 */
abstract class AbstractSet extends ArrayObject
{
	/**
	 * @param Entity[] $array
	 */
	public function __construct($array = array())
	{
		// Keep input array numerically indexed
		$array = array_values($array);
		
		parent::__construct($array);
	}
	
	/**
	 * Collects array of ID keys
	 * @return int[]
	 */
	public function collectIds()
	{
		//TODO: check if all elements are entities
		$ids = Entity::collectIds($this);

		return $ids;
	}
	
	/**
	 * @return Entity
	 */
	public function getFirstElement()
	{
		if ( ! isset($this[0])) {
			throw new Exception\RuntimeException("Element set is empty");
		}
		
		return $this[0];
	}
	
	/**
	 * @return Entity
	 */
	public function getLastElement()
	{
		$lastIndex = $this->count() - 1;
		
		if ( ! isset($this[$lastIndex])) {
			throw new Exception\RuntimeException("Element set is empty");
		}
		
		return $this[$lastIndex];
	}

}
