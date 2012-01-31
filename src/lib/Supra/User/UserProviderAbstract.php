<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authentication\Adapter;
use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception\UserNotFoundException;
use Supra\Authentication\Exception\AuthenticationFailure;
use Supra\Authentication\AuthenticationSessionNamespace;
use Supra\Session\SessionManager;
use Doctrine\ORM\EntityManager;


abstract class UserProviderAbstract
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
	 * Authentication adapter
	 * @var Adapter\AuthenticationAdapterInterface
	 */
	protected $authAdapter;
	
	/**
	 * Entity manager
	 * @var EntityManager 
	 */
	private $entityManager;

	/**
	 * @return EntityManager
	 */
	protected function getEntityManager()
	{
		return ObjectRepository::getEntityManager($this);
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
	 * TODO: throw exception on failure
	 * @return Entity\User
	 */
	public function getSignedInUser($updateSessionTime = true)
	{
		$sessionManager = $this->getSessionManager();
		$session = $this->getSessionSpace();
		
		if ( ! $updateSessionTime) {
			$sessionManager->getHandler()
					->setSilentAccess(true);
		}
		
		$user = $session->getUser();
		if ( ! ($user instanceof Entity\User)) {
			return null;
		}
		
		$sessionId = $sessionManager->getHandler()->getSessionId();
		
		$entityManager = $this->getEntityManager();
		$userSession = $entityManager->find(Entity\UserSession::CN(), $sessionId);
		
		if ( ! $userSession instanceof Entity\UserSession) {
			return null;
		}
		
		$sessionUser = $userSession->getUser();
		if ( ! ($sessionUser instanceof Entity\User)
				|| $sessionUser->getId() != $user->getId()) {
			
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
	
	final public function deleteUser($user)
	{
		$this->deleteUserSession($user);
		$this->doDeleteUser($user);
	}
	
	protected function deleteUserSession($user)
	{
		$userId = $user->getId();
		
		$em = $this->getEntityManager();
		
		$qb = $em->createQueryBuilder();
		
		$qb->delete(Entity\UserSession::CN(), 'us')
			->where('us.user = :userId')
			->setParameter('userId', $userId)
			->getQuery()->execute();
	}
	
}

