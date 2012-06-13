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
use Supra\User\Event\UserCreateEventArgs;
use Supra\Authentication\Event\EventArgs;
use Supra\User\Entity\UserPreference;

abstract class UserProviderAbstract implements UserProviderInterface
{
	/**
	 * Event names
	 */

	const EVENT_PRE_SIGN_IN = 'preSignIn';
	const EVENT_POST_SIGN_IN = 'postSignIn';
	const EVENT_PRE_SIGN_OUT = 'preSignOut';
	const EVENT_POST_SIGN_OUT = 'postSignOut';
	const EVENT_POST_USER_CREATE = 'postUserCreate';

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
	 * API method
	 * @param string $login
	 * @param AuthenticationPassword $password
	 */
	public final function authenticate($login, AuthenticationPassword $password)
	{
		$user = $this->doAuthenticate($login, $password);

		return $user;
	}

	/**
	 * Inner authentication method
	 * FIXME: maybe the "findUser" part should go to common authentication method?
	 * @param string $login
	 * @param AuthenticationPassword $password
	 * @return Entity\User
	 */
	protected function doAuthenticate($login, AuthenticationPassword $password)
	{
		$adapter = $this->getAuthAdapter();
		$login = $adapter->getFullLoginName($login);

		$user = $this->findUserByLogin($login);
		if (empty($user)) {
			throw new UserNotFoundException();
		}

		$adapter->authenticate($user, $password);

		return $user;
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
		$eventArgs = new Event\UserEventArgs($this);
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
		$eventArgs = new Event\UserEventArgs($this);
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
	 * Remove user
	 * @param Entity\User $user
	 */
	final public function deleteUser($user)
	{
		$this->deleteUserSession($user);
		$this->doDeleteUser($user);
	}

	/**
	 * Remove all user sessions
	 * @param Entity\User $user
	 */
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

	/**
	 * {@inheritDoc}
	 * @param Entity\User $user
	 * @param AuthenticationPassword $password
	 */
	public function credentialChange(Entity\User $user, AuthenticationPassword $password = null)
	{
		$this->authAdapter->credentialChange($user, $password);
	}

	/**
	 * Generates hash for password recovery
	 * @param Entity\User $user 
	 * @return string
	 */
	public function generatePasswordRecoveryHash(Entity\User $user, $time)
	{
		$salt = $user->getSalt();
		$email = $user->getEmail();

		$hashParts = array(
			$email,
			$time,
			$salt
		);

		$hash = md5(implode(' ', $hashParts));
		$hash = substr($hash, 0, 8);

		return $hash;
	}

	/**
	 * Wrapper around doInsertUser method, which stores newly created user
	 * and fires "user-post-create-event"
	 * @param Entity\User $user
	 */
	final public function insertUser(Entity\User $user)
	{
		$this->doInsertUser($user);

		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new UserCreateEventArgs($this);
		$eventArgs->setUserProvider($this);
		$eventArgs->setUser($user);
		$eventManager->fire(self::EVENT_POST_USER_CREATE, $eventArgs);
	}

	/**
	 * Store newly created group
	 * @param Entity\Group $group
	 */
	final public function insertGroup(Entity\Group $group)
	{
		$this->doInsertGroup($group);

		// TODO: similar to insertUser(), this method also could fire event, 
		// related with new group creation
	}

	/**
	 * Insert newly created user
	 * @param Entity\User $user
	 */
	abstract protected function doInsertUser(Entity\User $user);

	/**
	 * Insert newly created group
	 * @param Entity\Group $group
	 */
	abstract protected function doInsertGroup(Entity\Group $group);

	public function canUpdate()
	{
		return true;
	}

	public function canCreate()
	{
		return true;
	}
	
	/**
	 * Return an array (key => value) with user settings
	 * TODO: handle SiteUser preferences for SupraPortal
	 */
	public function getUserPreferences(Entity\User $user)
	{
		$preferences = array();
		
		$preferencesGroup = $user->getPreferencesGroup();
		
		if (is_null($preferencesGroup)) {
			$preferencesGroup = new Entity\UserPreferencesGroup();
			
			$em = ObjectRepository::getEntityManager($this);
			$em->persist($preferencesGroup);
			
			$user->setPreferencesGroup($preferencesGroup);
		}
		
		$collection = $preferencesGroup->getPreferencesCollection();
		
		foreach($collection as $userPreference) {
			$preferences[$userPreference->getName()] = $userPreference->getValue();
		}
		
		return $preferences;
	}
	
	/**
	 * @param Entity\User $user
	 * @param string $name
	 * @param mixed $value
	 */
	public function setUserPreference(Entity\User $user, $name, $value)
	{
		$em = ObjectRepository::getEntityManager($this);
		
		$userPreferencesGroup = $user->getPreferencesGroup();
		
		if (is_null($userPreferencesGroup)) {
			$userPreferencesGroup = new Entity\UserPreferencesGroup();
			$em->persist($userPreferencesGroup);
			
			$user->setPreferencesGroup($userPreferencesGroup);
		}
		
		$collection = $userPreferencesGroup->getPreferencesCollection();
		
		if ($collection->offsetExists($name)) {
			$preference = $collection->offsetGet($name);
			$preference->setValue($value);
		} else {
			$preference = new UserPreference($name, $value, $userPreferencesGroup);
			$em->persist($preference);
		}
		
		$collection->set($name, $preference);
		$em->flush();
	}
}
