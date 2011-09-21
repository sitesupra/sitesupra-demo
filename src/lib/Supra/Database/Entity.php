<?php

namespace Supra\Database;

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

	public static function findBy($criteria = array())
	{
		// not implemented yet
	}

	public static function getQueryBuilderResult(\Doctrine\ORM\QueryBuilder $queryBuilder)
	{
		$query = $queryBuilder->getQuery();
		return $query->getResult();
	}	
}
