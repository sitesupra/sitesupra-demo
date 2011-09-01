<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;

class UserProvider
{
	/**
	 * Validation filters
	 * @var type 
	 */
	private $validationFilters = array();
	
	/**
	 * Entity manager
	 * @var type 
	 */
	public $entityManager;

	public function __construct()
	{
		$this->entityManager = ObjectRepository::getEntityManager($this);
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