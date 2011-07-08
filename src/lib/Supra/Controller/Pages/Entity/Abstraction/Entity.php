<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\ORM\EntityManager,
		Supra\Database\Doctrine,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Doctrine\ORM\EntityRepository;

/**
 * Base entity class for Pages controller
 */
abstract class Entity
{
	/**
	 * Connection name
	 * @var string
	 */
	static private $connnection;

	/**
	 * Locks to pervent infinite loop calls
	 * @var array
	 */
	private $locks = array();

	/**
	 * Set connection name used by Pages controller
	 * @param string $connectionName
	 */
	public static function setConnectionName($connectionName = null)
	{
		self::$connnection = $connectionName;
	}

	/**
	 * Get configured doctrine entity manager
	 * @return EntityManager
	 */
	public static function getConnection()
	{
		return Doctrine::getInstance()->getEntityManager(self::$connnection);
	}

	/**
	 * @return EntityRepository
	 */
	public function getRepository()
	{
		$em = self::getConnection();
		$className = get_class($this);
		$rep = $em->getRepository($className);
		return $rep;
	}

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
	 * Set the property value. Return true on success, false on equal parameter,
	 * exception when argument not valid or different value was already set
	 * @param mixed $property
	 * @param mixed $value
	 * @return bool
	 * @throws Exception when trying to rewrite the property
	 *	or invalid argument is passed
	 */
	protected function writeOnce(&$property, $value)
	{
		$sourceEntity = get_class($this);
		if (empty($value)) {
			$this->unlockAll();
			throw new Exception\RuntimeException("Second argument sent to method
					$sourceEntity::writeOnce() cannot be empty");
		}
		if ( ! is_object($value)) {
			$this->unlockAll();
			throw new Exception\RuntimeException("Second argument sent to method 
					$sourceEntity::writeOnce() must be an object");
		}
		if ($property == $value) {
			return false;
		}
		if ( ! empty($property)) {
			$this->unlockAll();
			$targetEntity = get_class($value);
			throw new Exception\RuntimeException("The property $targetEntity is write-once,
					cannot rewrite with different value for $sourceEntity");
		}
		$property = $value;
		
		return true;
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
	 * Get discriminator key for the object ("page", "template", null if not found)
	 * @return string
	 */
	public function getDiscriminator()
	{
		$className = get_class($this);
		$em = self::getConnection();
		$metaData = $em->getClassMetadata($className);
		$key = \array_search($className, $metaData->discriminatorMap);
		if ($key !== false) {
			return $key;
		}
		return null;
	}

	/**
	 * Check if discriminators match for objects.
	 * If strict, they must be equal, if not strict, page object matches template object as well.
	 * As example PageData object can have Page block properties assigned to template block object.
	 * @param Entity $object
	 * @param boolean $strict
	 */
	public function matchDiscriminator(Entity $object, $strict = true)
	{
		$discrA = $this->getDiscriminator();
		$discrB = $object->getDiscriminator();

		\Log::debug("Checking discr matching for $this and $object: $discrA and $discrB");

		if ($discrA == $discrB) {
			return;
		}

		if ( ! $strict && ($discrA == 'page' && $discrB == 'template')) {
			return;
		}

		$this->unlockAll();
		
		throw new Exception\RuntimeException("The object discriminators do not match for {$this} and {$object}");
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

	public static function findBy($criteria = array())
	{
		// not implemented yet
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
		if ($entity == $this) {
			return true;
		}
		
		$id = $this->getId();
		
		if ( ! empty($id) && $entity->getId() == $id) {
			return true;
		}
		
		return false;
	}
}