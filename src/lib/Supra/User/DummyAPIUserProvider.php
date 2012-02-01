<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\Authentication\AuthenticationPassword;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Collections;
use Supra\Authentication\Exception\UserNotFoundException;


class DummyAPIUserProvider extends UserProviderAbstract implements UserProviderInterface
{
	/**
	 * Unique request key, 
	 * is used as site identifier when user/group list
	 * is requested from portal
	 * 
	 * @var string
	 */
	private $requestKey;
	
	/**
	 * Dummy data to simulate some responses from portal
	 * @var array
	 */
	private $dummyResponseData = array(
		'9cac7f895383156c0b9a480dab4751c82a2d9268' =>
			array('users' => array(
				array(
					'id' => 'uid00001',
					'login' => 'external-user-01',
					'email' => 'external-user-01@subdomain.supra7.vig',
					'name' => 'External admin',
					'password' => '$2a$12$dgTql6JLge1aI4vRe7zsHOSUOS8GKgBdaa28X1V1FsoHjFMxa9JrG', // admin
					'avatarId' => null,
					'personalAvatar' => false,
					'group' => '0013xw84z/external',
					'lastLoginTime' => '1234',
					'active' => true,
					'salt' => 'some_salt_001',
					'localeId' => 'lv',
				),
				
				array(
					'id' => 'uid00003',
					'login' => 'external-user-03',
					'email' => 'external-user-03@subdomain.supra7.vig',
					'name' => 'Another one external admin',
					'password' => '$2a$12$dgTql6JLge1aI4vRe7zsHOSUOS8GKgBdaa28X1V1FsoHjFMxa9JrG', // admin
					'avatarId' => null,
					'personalAvatar' => false,
					'group' => '0013xw84z/external',
					'lastLoginTime' => '1234',
					'active' => true,
					'salt' => 'some_salt_003',
					'localeId' => 'lv',
				),
				
				array(
					'id' => 'uid00002',
					'login' => 'external-user-02',
					'email' => 'external-user-02@subdomain.supra7.vig',
					'password' => '$2a$12$dgTql6JLge1aI4vRe7zsHOSUOS8GKgBdaa28X1V1FsoHjFMxa9JrG', // admin
					'name' => 'External supervisor',
					'group' => '0013xw8q/external',
					'lastLoginTime' => '12344',
					'active' => true,
					'salt' => 'some_salt_002',
				),
			),

			'groups' => array(
				array(
					'id' => '0013xw84z/external',
					'name' => 'admins',
					'isSuper' => true,
				),
				array(
					'id' => '0013xw8q/external',
					'name' => 'supers',
					'isSuper' => false,
				),
				array(
					'id' => '0013xw8hr/external',
					'name' => 'contribs',
					'isSuper' => false,
				),
			),
		),
	);
	
	/**
	 * 
	 */
	public function __construct()
	{
		$config = ObjectRepository::getIniConfigurationLoader($this);
		
		$key = $config->getValue('external_user_provider', 'key', null);
		$this->requestKey = $key;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function authenticate($login, AuthenticationPassword $password)
	{
		$adapter = $this->getAuthAdapter();
		
		$user = $this->findUserByLogin($login);
		if (empty($user)) {
			throw new UserNotFoundException();
		}

		$adapter->authenticate($user, $password);

		return $user;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function findUserByLogin($login)
	{
		$userData = $this->requestSingleRow(Entity\User::CN(), array('login' => $login));
		
		if ( ! empty($userData)) {
			$user = $this->createUserEntity($userData);
			return $user;
		}
		
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserById($id)
	{
		$userData = $this->requestSingleRow(Entity\User::CN(), array('id' => $id));
		
		if ( ! empty($userData)) {
			$user = $this->createUserEntity($userData);
			return $user;
		}
		
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function findUserByEmail($email)
	{
		$userData = $this->requestSingleRow(Entity\User::CN(), array('email' => $email));
		
		if ( ! empty($userData)) {
			$user = $this->createUserEntity($userData);
			return $user;
		}
		
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupByName($name)
	{
		$groupData = $this->requestSingleRow(Entity\Group::CN(), array('name' => $name));
		
		if ( ! empty($groupData)) {
			$group = $this->createGroupEntity($groupData);
			return $group;
		}
		
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupById($id)
	{
		$groupData = $this->requestSingleRow(Entity\Group::CN(), array('id' => $id));
		
		if ( ! empty($groupData)) {
			$group = $this->createGroupEntity($groupData);
			return $group;
		}
		
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function findAllUsers()
	{
		$users = array();
		$userData = $this->requestData(Entity\User::CN());
		
		if ( ! empty($userData)) {
			foreach($userData as $user) {
				$users[] = $this->createUserEntity($user);
			}
		}
		
		return $users;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllGroups()
	{
		$groups = array();
		$groupData = $this->requestSingleRow(Entity\Group::CN());
		
		if ( ! empty($groupData)) {
			foreach($groupData as $group)
			$groups[] = $this->createGroupEntity($group);
		}
		
		return $groups;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllUsersInGroup(Entity\Group $group)
	{
		$users = array();
		$groupId = $group->getId();
		
		$userData = $this->requestSingleRow(Entity\Group::CN(), array('group' => $groupId));
		
		if ( ! empty($userData)) {
			foreach($userData as $user) {
				$users[] = $this->createUserEntity($user);
			}
		}
		
		return $users;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function createUser()
	{
		$user = new Entity\User();
		
		return $user;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function doDeleteUser(Entity\User $user) 
	{
		
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function updateUser(Entity\User $user)
	{
		
	}
	
	/**
	 * Creates detached User entity filled with data from input array 
	 * @param array $userData
	 * @return Entity\User
	 */
	private function createUserEntity(array $userData)
	{
		$user = new Entity\User();
		
		//$userSessions = $this->getEntityManager()
		//		->getRepository(Entity\UserSession::CN())
		//		->findByUser($userData['id']);
		
		$userGroup = $this->findGroupById($userData['group']);
		
		//$userData['sessions'] = $userSessions;
		$userData['group'] = $userGroup;
		
		$user->fillFromArray($userData);
		
		$this->getEntityManager()
				->detach($user);
		
		return $user;
	}
	
	/**
	 * Creates detached Group entity filled with data from input array
	 * @param array $groupData
	 * @return Entity\Group
	 */
	private function createGroupEntity(array $groupData)
	{
		$group = new Entity\Group();
		$group->fillFromArray($groupData);
		
		$this->getEntityManager()
				->detach($group);
		
		return $group;
	}
	
	/**
	 * Helper wrapper for self::requestData() method, to get single result row
	 * @param type $element
	 * @param type $searchCriteria
	 * @return array
	 */
	private function requestSingleRow($element, $searchCriteria)
	{
		$response = $this->requestData($element, $searchCriteria);
		
		if (empty($response)) {
			return null;
		}
		
		if (count($response) > 1) {
			throw new Exception\RuntimeException('Request returned more than one row');
		}
		
		return array_pop($response);
	}
	
	/**
	 * Request simulator, returns dummy data 
	 * @param string $entityName
	 * @param array $searchCriteria
	 */
	private function requestData($entityName, $searchCriteria = null)
	{
		$response = array(); $array = array();
		
		switch($entityName) {
			case Entity\User::CN():
				$array = $this->dummyResponseData[$this->requestKey]['users'];
				break;
			
			case Entity\Group::CN():
				$array = $this->dummyResponseData[$this->requestKey]['groups'];
				break;
		}
		
		if ( ! empty($searchCriteria)) {
			$key = array_shift(array_keys($searchCriteria));
			$value = array_shift($searchCriteria);

			foreach($array as $item) {
				if (isset($item[$key]) && $item[$key] === $value) {
					$response[] = $item;
				}
			}
			
			return $response;
		}
		
		return $array;
	}
		
}
