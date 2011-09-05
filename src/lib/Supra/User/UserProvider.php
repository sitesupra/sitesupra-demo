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
	
	protected $authAdapter = null;
	
	public function __construct()
	{
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}
	/**
	 * Adds validation filter to array
	 * @param type $validationFilter 
	 */
	public function addValidationFilter($validationFilter)
	{
		$this->validationFilters[] = $validationFilter;
	}
	
	/**
	 * Validates user with all filters
	 * @param Entity\User $user 
	 */
	public function validate(Entity\User $user)
	{
		foreach ($this->validationFilters as $filter) {
			$filter->validateUser($user);
		}
	}
	
	public function getAuthAdapter()
	{
		return $this->authAdapter;
	}

	public function setAuthAdapter($authAdapter)
	{
		$this->authAdapter = $authAdapter;
	}

	/**
	 * Passes user to authentication adapter and returns
     * false or User Object
	 * @param string $login 
	 */
	public function authenticate($login, $password)
	{		
		$adapter = $this->getAuthAdapter();
		$user = $adapter->findUser($login, $password);
		
		$result = $adapter->authenticate($user, $password);
		
		if (! $result) {
			return false;
		}
		
		return $user;
	}
	
		
	/**
	 * Generates password for database
	 * @param string $password
	 * @param string $salt
	 * @return string 
	 */
	public function generatePasswordHash($password, $salt)
	{
		return sha1($password . $salt); 
	}
}