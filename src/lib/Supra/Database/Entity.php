<?php

namespace Supra\Database;

use Doctrine\Common\Collections\Collection;

/**
 * @MappedSuperclass
 */
abstract class Entity 
{
	/**
	 * @Id
	 * @Column(type="supraId20")
	 * @var string
	 */
	protected $id;
	
	/**
	 * Locks to pervent infinite loop calls
	 * @var array
	 */
	private $locks = array();
	
	/**
	 * Loads full name of the class
	 * TODO: Decide is it smart
	 */
	public static function CN()
	{
		return get_called_class();
	}
	
	/**
	 * Allocates ID
	 */
	public function __construct()
	{
		$this->regenerateId();
	}
	
	/**
	 * Id generation strategy
	 */
	protected function regenerateId()
	{
		$this->id = self::generateId(get_class($this));
	}
	
	/**
	 * Identification getter
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Lock to prevent infinite loops
	 * @param string $name
	 * @return boolean
	 */
	protected function lock($name)
	{
		if ( ! array_key_exists($name, $this->locks)) {
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
		if ( ! array_key_exists($name, $this->locks)) {
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
	
	/**
	 * Adds an element to collection preserving uniqueness of fields
	 * @param Collection $collection
	 * @param Entity $newItem
	 * @param string $uniqueField
	 * @return boolean true if added, false if already the same instance has been added
	 * @throws Exception\RuntimeException if element with the same unique field values exists
	 */
	protected function addUnique(Collection $collection, Entity $newItem, $uniqueField = null)
	{
		if ($collection->contains($newItem)) {
			return false;
		}
		
		if (is_null($uniqueField)) {
			$collection->add($newItem);
		} else {
			$indexBy = $newItem->getProperty($uniqueField);
			
			if ($collection->offsetExists($indexBy)) {
				throw new Exception\RuntimeException("Cannot add value '{$newItem}' to '{$this}': element by {$uniqueField}={$indexBy} already exists in the collection");
			}
			
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
		$method = 'get' . ucfirst($name);
		
		if ( ! method_exists($this, $method)) {
			$this->unlockAll();
			$class = get_class($this);
			throw new Exception\RuntimeException("Could not found getter function for object
					$class property $name");
		}
		$value = $this->$method();
		
		return $value;
	}
	
	/**
	 * Time sortable ID
	 */
	public static function generateId($className = '') 
	{
		$time = microtime(true) - 1324027985;
		$time = (int) (1000 * $time);
		$time = base_convert($time, 10, 36);
		$time = substr($time, 0, 9);
		$time = str_pad($time, 9, '0', STR_PAD_LEFT);
		
		$random = sha1(uniqid($className, true));
		$random = base_convert($random, 16, 36);
		$random = substr($random, 0, 11);
		$random = str_pad($random, 11, '0', STR_PAD_LEFT);
		
		return $time . $random;
	}
}
