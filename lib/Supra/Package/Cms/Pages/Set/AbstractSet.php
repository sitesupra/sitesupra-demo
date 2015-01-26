<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * Set containing entity objects
 */
abstract class AbstractSet extends \ArrayObject
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
			throw new \RuntimeException("Element set is empty");
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
			throw new \RuntimeException("Element set is empty");
		}
		
		return $this[$lastIndex];
	}
	
	/**
	 * @param string $id
	 * @return Entity
	 */
	public function findById($id)
	{
		foreach ($this as $entity) {
			if ($entity->getId() === $id) {
				return $entity;
			}
		}
		
		return null;
	}
	
	/**
	 * Merges array into the object
	 * @param mixed $array
	 */
	public function appendArray($array)
	{
		foreach ($array as $item) {
			$this->append($item);
		}
	}

}
