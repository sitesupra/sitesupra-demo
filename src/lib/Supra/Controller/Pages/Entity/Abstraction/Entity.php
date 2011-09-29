<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database;

/**
 * Base entity class for Pages controller
 * @MappedSuperclass
 */
abstract class Entity extends Database\Entity
{
	/**
	 * Constant for Doctrine discriminator, used to get entity type without entity manager
	 */
	const DISCRIMINATOR = null;
	
	/** 
	 * Is used as additional ID for _history scheme
	 * NB! Must be not null, or merge between EM's will fail
	 * @var RevisionData
	 */
	protected $revision = '';
	
	/**
	 * Creates log writer instance
	 */
	protected function log()
	{
		return ObjectRepository::getLogger($this);
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
		if ($property === $value) {
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
	 * Get discriminator key for the object ("page", "template", null if not found)
	 * @return string
	 */
	public function getDiscriminator()
	{
		return static::DISCRIMINATOR;
	}

	/**
	 * Check if discriminators match for objects.
	 * If strict, they must be equal, if not strict, page object matches template object as well.
	 * As example PageLocalization object can have Page block properties assigned to template block object.
	 * @param Entity $object
	 * @param boolean $strict
	 */
	public function matchDiscriminator(Entity $object, $strict = true)
	{
		if ( ! $object instanceof Entity) {
			throw new Exception\LogicException("Entity not passed to the matchDiscriminator method");
		}
		
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
	 * Is used for _history handling
	 * @param RevisionData $revisionData 
	 */
	public function setRevisionId ($revisionId) {
		$this->revision = $revisionId;
	}
	public function getRevisionId () {
		return $this->revision;
	}
	/*
	public function setRevisionData ($revisionData) {
		$this->revision = $revisionData;
	}
	public function getRevisionData () {
		return $this->revision;
	}
	 */
}
