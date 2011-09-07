<?php

namespace Supra\FileStorage\Entity\Abstraction;

use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * Base entity class for file storage
 */
abstract class Entity
{
	/**
	 * Locks to pervent infinite loop calls
	 * @var array
	 */
	private $locks = array();
	
	/**
	 * Id getter is mandatory
	 * @return integer
	 */
	abstract public function getId();

	/**
	 * Lock to prevent infinite loops
	 * @param string $name
	 * @return boolean
	 */
	protected function lock($name)
	{
		if ( ! \array_key_exists($name, $this->locks)) {
			$this->locks[$name] = true;
			return true;
		}
		return false;
	}
	/**
	 * Unlock locked parameter
	 * @param string $name
	 * @return boolean
	 */
	protected function unlock($name)
	{
		if ( ! \array_key_exists($name, $this->locks)) {
			return false;
		}
		unset($this->locks[$name]);
		return true;
	}

	/**
	 * Unlocks all locks, must be run before throwing exception
	 */
	protected function unlockAll()
	{
		$this->locks = array();
	}

	/**
	 * Adds an element to collection preserving uniqueness of fields
	 * @param Collection $collection
	 * @param Entity $newItem
	 * @param string $uniqueField
	 * @return boolean true if added, false if already the same instance has been added
	 * @throws Exception\RuntimeException if element with the same unique field values exists
	 */
	protected function addUnique(Collection $collection, $newItem, $uniqueField = null)
	{
		if ($collection->contains($newItem)) {
			return false;
		}

		if (is_null($uniqueField)) {
			$collection->add($newItem);
		} else {
			//FIXME: ugly
			$getter = 'get' . $uniqueField;
			$indexBy = $newItem->$getter();

			$collection->set($indexBy, $newItem);
		}

		return true;
	}

	/**
	 * Get property of an object by name
	 * @param string $name
	 * @return mixed
	 * @throws Exception\RuntimeException if property getter method is not found
	 */
	public function getProperty($name)
	{
		$method = 'get' . \ucfirst($name);
		if ( ! \method_exists($this, $method)) {
			$this->unlockAll();
			$class = \get_class($this);
			throw new Exception\RuntimeException("Could not found getter function for object
					$class property $name");
		}
		$value = $this->$method();
		return $value;
	}

	/**
	 * Asserts that the object is instance of class
	 * @param Entity $instance
	 * @param string $class
	 * @param string $method
	 * @throws Exception\RuntimeException if the instance check fails
	 */
	protected function isInstanceOf(Entity $instance, $class, $method)
	{
		if ( ! ($instance instanceof $class)) {
			$this->unlockAll();
			throw new Exception\RuntimeException("Object can accept instance of $class in method $method");
		}
	}

	/**
	 * Object string value
	 * @return string
	 */
	public function __toString()
	{
		$id = $this->getId();
		if ( ! empty($id)) {
			return get_class($this) . '#' . $id;
		}

		//TODO: could include all property values if no ID
		return get_class($this) . '#unstored';
	}

	/**
	 * Collects Id array from entity collection
	 * @param array|Collection|\Traversable $entities
	 * @return array
	 */
	public static function collectIds($entities)
	{
		$ids = array();

		foreach ($entities as $entity) {
			$ids[] = $entity->getId();
		}

		return $ids;
	}

	public static function getQueryBuilderResult(\Doctrine\ORM\QueryBuilder $queryBuilder)
	{
		$query = $queryBuilder->getQuery();
		return $query->getResult();
	}

	/**
	 * Are the both entity records equal in means of database record
	 * @param Entity $entity
	 * @return boolean
	 */
	public function equals(Entity $entity)
	{
		// Equals if matches
		if ($entity === $this) {
			return true;
		}

		$id = $this->getId();

		if ( ! empty($id) && $entity->getId() == $id) {
			return true;
		}

		return false;
	}
}