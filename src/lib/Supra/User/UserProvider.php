<?php

namespace Supra\User;

use Supra\User\Entity;

class UserProvider
{

	private $validationFilters = array();
	
	public $entityManager;
	public $repository;
	
	protected static $instance;
	
	const USER_ENTITY = '\Supra\User\Entity\User';
	
	
	/**
	 * Protecting from new UserProvider
	 */
	private function __construct()
	{
		
	}

	/**
	 * Protecting from cloning
	 */
	private function __clone()
	{
		
	}

	/**
	 * Returning only one instance of object
	 *
	 * @return UserProvider
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new UserProvider;
		}
		return self::$instance;
	}

	public function getEntityManager()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}

	public function getRepository()
	{
		return $this->getEntityManager()->getRepository(self::USER_ENTITY);
	}

		public function addValidationFilter($validationFilter)
	{
		$this->validationFilters[] = $validationFilter;
	}

	public function validate(Entity\User $user)
	{
		foreach ($this->validationFilters as $filter) {
			$filter->validateUser($user);
		}
		
	}

}