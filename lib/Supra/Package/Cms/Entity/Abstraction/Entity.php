<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Package\Cms\Entity\PageLocalization;

use Supra\Controller\Pages\Exception;

/**
 * Base entity class for Pages component.
 * @MappedSuperclass
 */
abstract class Entity
{
	const PAGE_DISCR = 'page';
	const GROUP_DISCR = 'group';
	const APPLICATION_DISCR = 'application';
	const TEMPLATE_DISCR = 'template';

	/**
	 * Constant for Doctrine discriminator, used to get entity type without entity manager
	 */
	const DISCRIMINATOR = null;

	/**
	 * @Id
	 * @Column(type="supraId20")
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Self increasing key used for ID generation, put right after microtime as
	 * 2 characters to make sure the ID is always increasing.
	 *
	 * @var integer
	 */
	private static $idSequence = 0;

	/**
	 * Locks to prevent infinite loop calls
	 * @var array
	 */
	private $locks = array();

	/**
	 * Loads full name of the class
	 * @TODO: Decide is it smart
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
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Doctrine's safe __clone implementation.
	 */
	public function __clone()
	{
		if (! empty($this->id)) {
			$this->regenerateId();
		}
	}

	/**
	 * Lock to prevent infinite loops
	 *
	 * @param string $name
	 * @return bool
	 */
	protected function lock($name)
	{
		if (! array_key_exists($name, $this->locks)) {
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
		if (! array_key_exists($name, $this->locks)) {
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
	public function equals(Entity $entity = null)
	{
		if ($entity === null) {
			return false;
		}

		// Equals if matches
		if ($entity === $this) {
			return true;
		}

		$id = $this->getId();

		if ( ! empty($id) && $entity->getId() === $id) {
			return true;
		}

		return false;
	}

	/**
	 * @param Entity $entity1
	 * @param Entity $entity2
	 * @return bool
	 */
	public static function areEqual(Entity $entity1 = null, Entity $entity2 = null)
	{
		if ($entity1 === $entity2) {
			return true;
		}

		if ($entity1 instanceof Entity || $entity2 instanceof Entity) {
			return false;
		}

		$equals = $entity1->equals($entity2);

		return $equals;
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
	 * @TODO: not sure if this one is needed here.
	 *
	 * Get property of an object by name.
	 *
	 * @param string $name
	 * @return mixed
	 * @throws \RuntimeException if property getter method is not found
	 */
	public function getProperty($name)
	{
		$method = 'get' . ucfirst($name);

		if (! method_exists($this, $method)) {
			$this->unlockAll();
			$class = get_class($this);
			throw new \RuntimeException("Could not found getter function for object	$class property $name");
		}

		$value = $this->$method();

		return $value;
	}

	/**
	 * Generates time sortable ID.
	 *
	 * @param string $className
	 * @return string
	 */
	public static function generateId($className = '')
	{
		// TODO: on 32bit systems this might generate low precision hashes due to float number usage in the base_convert function
		$timeParts = explode(' ', microtime(false));
		$timeParts[0] = substr($timeParts[0], 2, 3);
		$time = ((int) $timeParts[1] - 1324027985) . $timeParts[0];
		$time = base_convert($time, 10, 36);
		$time = substr($time, -9);
		$time = str_pad($time, 9, '0', STR_PAD_LEFT);

		// Local sequence usage
		$sequence = base_convert(self::$idSequence++, 10, 36);
		$sequence = substr($sequence, -2);
		$sequence = str_pad($sequence, 2, '0', STR_PAD_LEFT);

		$random = sha1(uniqid($className, true));
		$random = base_convert($random, 16, 36);
		$random = substr($random, -9);
		$random = str_pad($random, 9, '0', STR_PAD_LEFT);

		return $time . $sequence . $random;
	}

	/**
	 * Set the property value. Return true on success, false on equal parameter,
	 * exception when argument not valid or different value was already set
	 * @param mixed $property
	 * @param mixed $value
	 * @return bool
	 * @throws \RuntimeException when trying to rewrite the property
	 * 	or invalid argument is passed
	 */
	protected function writeOnce(&$property, $value)
	{
		$sourceEntity = get_class($this);
		if (empty($value)) {
			$this->unlockAll();
			throw new \RuntimeException("Second argument sent to method	$sourceEntity::writeOnce() cannot be empty");
		}
		if ( ! is_object($value)) {
			$this->unlockAll();
			throw new \RuntimeException("Second argument sent to method $sourceEntity::writeOnce() must be an object");
		}
		if ($property === $value) {
			return false;
		}
		if (! empty($property)) {

			$this->unlockAll();

			throw new \RuntimeException(sprintf(
				"The property [%s] is write-once, cannot rewrite with different value for [%]",
				get_class($value),
				$sourceEntity
			));
		}
		$property = $value;

		return true;
	}

	/**
	 * Check if discriminators match for objects.
	 * They must be equal, with exceptions:
	 * 		* PageLocalization object can have Page block properties assigned to template block object
	 * 		* Application objects can be bound to Page objects except case with AbstractPage <-> Localization reference
	 * @param Entity $object
	 */
	public function matchDiscriminator(Entity $object)
	{
		if ( ! $object instanceof Entity) {
			throw new Exception\LogicException("Entity not passed to the matchDiscriminator method");
		}

		$discrA = $this::DISCRIMINATOR;
		$discrB = $object::DISCRIMINATOR;

//		$this->log()->debug("Checking discr matching for $this and $object: $discrA and $discrB");

		if ($discrA == $discrB) {
			return;
		}

		// Allow binding page elements to application elements (except AbstractPage <-> Localization)
		if ($discrA != self::TEMPLATE_DISCR && $discrB != self::TEMPLATE_DISCR) {
			if ( ! ($this instanceof AbstractPage && $object instanceof Localization)) {
				return;
			}
		}

		/*
		 * Allow template elements being bound to the page elements in case of
		 * block property set to page localization and template block
		 */
		if ($this instanceof PageLocalization && $object instanceof TemplateBlock) {
			return;
		}

		$this->unlockAll();

		throw new Exception\RuntimeException("The object discriminators do not match for {$this} and {$object}");
	}
}
