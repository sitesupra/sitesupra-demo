<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Supra\Authentication\Adapter;
use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception\UserNotFoundException;
use Supra\Authentication\Exception\AuthenticationFailure;
use Supra\Authentication\AuthenticationSessionNamespace;
use Supra\Session\SessionManager;

class UserProvider
{
	/**
	 * Event names
	 */
	const EVENT_PRE_SIGN_IN = 'preSignIn';
	const EVENT_POST_SIGN_IN = 'postSignIn';
	const EVENT_PRE_SIGN_OUT = 'preSignOut';
	const EVENT_POST_SIGN_OUT = 'postSignOut';
	
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
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return ObjectRepository::getEntityManager($this);
	}
	
	/**
	 * @return SessionManager
	 */
	public function getSessionManager()
	{
		$manager = ObjectRepository::getSessionManager($this);
		
		return $manager;
	}
	
	/**
	 * @return AuthenticationSessionNamespace
	 */
	public function getSessionSpace()
	{
		$session = $this->getSessionManager()
				->getAuthenticationSpace();
		
		return $session;
	}

	/**
	 * Adds validation filter to array
	 * @param Validation\UserValidationInterface $validationFilter 
	 */
	public function addValidationFilter($validationFilter)
	{
		ObjectRepository::setCallerParent($validationFilter, $this);
		$this->validationFilters[] = $validationFilter;
	}

	/**
	 * Validates user with all filters
	 * @param Entity\User $user 
	 */
	public function validate(Entity\User $user)
	{
		foreach ($this->validationFilters as $filter) {
			/* @var $filter Validation\UserValidationInterface */
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
		ObjectRepository::setCallerParent($authAdapter, $this);
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
		
		$login = $adapter->getFullLoginName($login);
		
		$user = $this->findUserByLogin($login);

		// Try finding the user from adapter
		if (empty($user)) {
			$user = $adapter->findUser($login, $password);

			if (empty($user)) {
				throw new UserNotFoundException();
			}

			$entityManager = $this->getEntityManager();
			$entityManager->persist($user);
			$entityManager->flush();
		}

		$adapter->authenticate($user, $password);

		return $user;
	}
	
	/**
	 * Saves the user in the session storage
	 * @param Entity\User $user
	 */
	public function signIn(Entity\User $user)
	{
		$entityManager = $this->getEntityManager();
		
		// Trigger pre sign in listener
		$eventArgs = new Event\UserEventArgs();
		$eventArgs->entityManager = $entityManager;
		$eventArgs->user = $user;
		
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(self::EVENT_PRE_SIGN_IN, $eventArgs);
		
		// Create session record
		$sessionEntity = new Entity\UserSession();
		$sessionEntity->setUser($user);
		$entityManager->persist($sessionEntity);
		$sessionId = $sessionEntity->getId();
		
		// Set entity generated session ID
		$sessionManager = $this->getSessionManager();
		$sessionManager->changeSessionId($sessionId);
		
		// Store user inside session storage
		$session = $this->getSessionSpace();
		$session->setUser($user);
		
		$entityManager->flush();
		
		// Trigger post sign in listener
		$eventManager->fire(self::EVENT_POST_SIGN_IN, $eventArgs);
	}
	
	/**
	 * Removes the user from the session storage
	 */
	public function signOut()
	{
		$entityManager = $this->getEntityManager();
		$session = $this->getSessionSpace();
		$user = $session->getUser();
		
		// Remove the user from the session storage
		$session->removeUser();
		
		// Trigger pre sign out listeners
		$eventArgs = new Event\UserEventArgs();
		$eventArgs->entityManager = $entityManager;
		$eventArgs->user = $user;
		
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(self::EVENT_PRE_SIGN_OUT, $eventArgs);
		
		// Find and remove user session from the database
		$sessionManager = $this->getSessionManager();
		$sessionId = $sessionManager->getHandler()->getSessionId();
		$sessionEntity = $entityManager->find(Entity\UserSession::CN(), $sessionId);
		
		if ($sessionEntity instanceof Entity\UserSession) {
			$entityManager->remove($sessionEntity);
			$entityManager->flush();
		}
		
		// Trigger post sign out listeners
		$eventManager->fire(self::EVENT_POST_SIGN_OUT, $eventArgs);
	}
	
	/**
	 * TODO: throw exception on failure
	 * @return Entity\User
	 */
	public function getSignedInUser($updateSessionTime = true)
	{
		$sessionManager = $this->getSessionManager();
		$session = $this->getSessionSpace();
		$user = $session->getUser();
		
		$sessionId = $sessionManager->getHandler()->getSessionId();
		
		$entityManager = $this->getEntityManager();
		$userSession = $entityManager->find(Entity\UserSession::CN(), $sessionId);
		
		if ( ! $userSession instanceof Entity\UserSession) {
			return null;
		}
		
		if ($userSession->getUser() !== $user) {
			return null;
		}
		
		// Update the last access time
		if ($updateSessionTime) {
			$userSession->setModificationTime();
			$entityManager->flush();
		}
		
		return $user;
	}

	/**
	 * Find user by login
	 * @param string $login
	 * @return Entity\User 
	 */
	public function findUserByLogin($login)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
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
		$entityManager = $this->getEntityManager();
		
		return $entityManager->find(Entity\User::CN(), $id);
	}

	/**
	 * Find group by name
	 * @param string $name
	 * @return Entity\Group 
	 */
	public function findGroupByName($name)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\Group::CN());
		$group = $repo->findOneByName($name);

		return $group;
	}

	/**
	 * Find group by id
	 * @param string $id
	 * @return Entity\Group
	 */
	public function findGroupById($id)
	{
		$entityManager = $this->getEntityManager();
		
		return $entityManager->find(Entity\Group::CN(), $id);
	}
	
	/**
	 * Find user/group by ID
	 * @param string $id
	 * @return Entity\AbstractUser
	 */
	public function findById($id)
	{
		$entityManager = $this->getEntityManager();
		
		return $entityManager->find(Entity\AbstractUser::CN(), $id);
	}

	/**
	 * @return array
	 */
	public function findAllUsers()
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$users = $repo->findAll();

		return $users;
	}

	/**
	 * @return array
	 */
	public function findAllGrups()
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\Group::CN());
		$groups = $repo->findAll();

		return $groups;
	}

	/**
	 * @param Entity\Group $group
	 * @return array
	 */
	public function getAllUsersInGroup(Entity\Group $group)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$users = $repo->findBy(array('group' => $group->getId()));
		
		return $users;
	}

}
