<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Supra\Authentication\Adapter;
use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception\UserNotFoundException;
use Supra\Authentication\Exception\AuthenticationFailure;

class UserProvider
{

	/**
	 * Validation filters
	 * @var array 
	 */
	private $validationFilters = array();

	/**
	 * Entity manager
	 * @var EntityManager 
	 */
	public $entityManager;

	/**
	 * Authentication adapter
	 * @var Adapter\AuthenticationAdapterInterface
	 */
	protected $authAdapter;

	/**
	 * Binds entity manager
	 */
	public function __construct()
	{
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * Override the entity manager
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	/**
	 * Adds validation filter to array
	 * @param array $validationFilter 
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

	/**
	 * Returns authentication adapter object
	 * @return Adapter\AuthenticationAdapterInterface
	 */
	public function getAuthAdapter()
	{
		return $this->authAdapter;
	}

	/**
	 * Sets authentication adapter
	 * @param Adapter\AuthenticationAdapterInterface $authAdapter 
	 */
	public function setAuthAdapter(Adapter\AuthenticationAdapterInterface $authAdapter)
	{
		$this->authAdapter = $authAdapter;
	}

	/**
	 * Passes user to authentication adapter
	 * @param string $login 
	 * @param AuthenticationPassword $password
	 * @return Entity\User
	 * @throws AuthenticationFailure
	 */
	public function authenticate($login, AuthenticationPassword $password)
	{
		$adapter = $this->getAuthAdapter();

		$user = $this->findUserByLogin($login);

		// Try finding the user from adapter
		if (empty($user)) {
			$user = $adapter->findUser($login, $password);

			if (empty($user)) {
				throw new UserNotFoundException();
			}

			$this->entityManager->persist($user);
			$this->entityManager->flush();
		}

		$adapter->authenticate($user, $password);

		return $user;
	}

	/**
	 * Find user by login
	 * @param string $login
	 * @return Entity\User 
	 */
	public function findUserByLogin($login)
	{
		$repo = $this->entityManager->getRepository(Entity\User::CN());
		$user = $repo->findOneByLogin($login);

		if (empty($user)) {
			return null;
		}
		return $user;
	}

	/**
	 * Find user by id
	 * @param string $id
	 * @return Entity\User 
	 */
	public function findUserById($id)
	{
		return $this->entityManager->find(Entity\User::CN(), $id);
	}

	/**
	 * Find group by name
	 * @param string $name
	 * @return Entity\Group 
	 */
	public function findGroupByName($name)
	{
		$repo = $this->entityManager->getRepository(Entity\Group::CN());
		$group = $repo->findOneByName($name);

		if (empty($group)) {
			return null;
		}
		return $group;
	}

	/**
	 * Find group by id
	 * @param type $id
	 * @return Entity\Group
	 */
	public function findGroupById($id)
	{
		return $this->entityManager->find(Entity\Group::CN(), $id);
	}
	
	/**
	 * Find user/group by ID
	 * @param string $id
	 * @return Entity\Abstraction\User
	 */
	public function findById($id)
	{
		return $this->entityManager->find(Entity\Abstraction\User::CN(), $id);
	}

	public function findAllUsers()
	{
		$repo = $this->entityManager->getRepository(Entity\User::CN());
		$users = $repo->findAll();

		return $users;
	}

	public function findAllGrups()
	{
		$repo = $this->entityManager->getRepository(Entity\Group::CN());
		$groups = $repo->findAll();

		return $groups;
	}

	public function getAllUsersInGroup(Entity\Group $group)
	{
		$repo = $this->entityManager->getRepository(Entity\User::CN());
		$users = $repo->findBy(array('group' => $group->getId()));
		return $users;
	}

}
