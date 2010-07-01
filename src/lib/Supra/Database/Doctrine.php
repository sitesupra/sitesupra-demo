<?php

namespace Supra\Database;

use Doctrine\ORM\EntityManager;

/**
 * Doctrine helper class
 */
class Doctrine
{
	/**
	 * Singleton instance
	 * @var Doctrine
	 */
	static protected $instance;

	/**
	 * List of doctrine entity managers
	 * @var EntityManager[]
	 */
	protected $entityManagers = array();

	/**
	 * Singleton pattern
	 * @return Doctrine
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Get doctrine entity manager
	 * @param string $name
	 * @return EntityManager
	 */
	public function getEntityManager($name = null)
	{
		if (isset($this->entityManagers[$name])) {
			return $this->entityManagers[$name];
		}
		throw new Exception("Entity manager '$name' has not been found");
	}

	/**
	 * Sets entity manager
	 * @param string $name
	 * @param EntityManager $em
	 */
	public function setEntityManager($name, EntityManager $em)
	{
		$this->entityManagers[$name] = $em;
	}

	/**
	 * Sets default entity manager
	 * @param EntityManager $em
	 */
	public function setDefaultEntityManager(EntityManager $em)
	{
		$this->setEntityManager(null, $em);
	}
}