<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\Authentication\AuthenticationPassword;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Collections;
use Supra\Authentication\Exception\UserNotFoundException;
use Supra\Remote\Client\RemoteCommandService;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Console\Output\ArrayOutput;
use Supra\Console\Output\ArrayOutputWithData;

class RemoteUserProvider extends UserProviderAbstract
{

	/**
	 * @var RemoteCommandService
	 */
	private $service;

	/**
	 * @var string
	 */
	private $remoteApiEndpointId;

	const REMOTE_COMMAND_USER = 'su:remote:find_user';
	const REMOTE_COMMAND_GROUP = 'su:remote:find_group';

	public function __construct()
	{
		$this->service = new RemoteCommandService();
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
		return $this->findUserByCriteria(array('field' => 'login', 'value' => $login));
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserById($id)
	{
		return $this->findUserByCriteria(array('field' => 'id', 'value' => $id));
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByEmail($email)
	{
		return $this->findUserByCriteria(array('field' => 'email', 'value' => $email));
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByName($name)
	{
		return $this->findUserByCriteria(array('field' => 'name', 'value' => $name));
	}

	public function findAllGroups()
	{
		throw new \Exception('Not implemented');
	}

	public function findAllUsers()
	{
		return $this->findUserByCriteria(array('--all-users' => true));
	}

	public function findGroupById($id)
	{
		return $this->findGroupByCriteria(array('field' => 'id', 'value' => $id));
	}

	public function findGroupByName($name)
	{
		return $this->findGroupByCriteria(array('field' => 'name', 'value' => $name));
	}

	public function getAllUsersInGroup(Entity\Group $group)
	{
		return $this->findGroupByCriteria(array('field' => '', 'value' => $id));
	}

	public function loadUserByUsername($username)
	{
		throw new \Exception('Not implemented');
	}

	public function refreshUser(UserInterface $user)
	{
		throw new \Exception('Not implemented');
	}

	public function supportsClass($class)
	{
		throw new \Exception('Not implemented');
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
	public function createGroup()
	{
		$group = new Entity\Group();

		return $group;
	}

	/**
	 * {@inheritDoc}
	 */
	public function doDeleteUser(Entity\User $user)
	{
		throw new \Exception('Not implemented');
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateUser(Entity\User $user)
	{
		throw new \Exception('Not implemented');
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateGroup(Entity\Group $group)
	{
		throw new \Exception('Not implemented');
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
	 * Request simulator, returns dummy data 
	 * @param string $entityName
	 * @param array $searchCriteria
	 */
	private function requestData($entityName, $searchCriteria = null)
	{
		$logger = ObjectRepository::getLogger($this);
		$remoteApiEndpointId = $this->getRemoteApiEndpointId();
		if (empty($remoteApiEndpointId)) {
			$logger->error('Remote api endpoint id is not configured.
				Add [' . RemoteCommandService::INI_SECTION_NAME . '] section to supra.ini and then assign
					remote api endpoint id to ' . __CLASS__);

			return;
		}

		$response = null;

		if ( ! is_array($searchCriteria)) {
			$searchCriteria = array();
		}

		$output = new ArrayOutputWithData();

		switch ($entityName) {
			case Entity\User::CN():

				$searchCriteria = array('command' => self::REMOTE_COMMAND_USER) + $searchCriteria;

				$input = new ArrayInput($searchCriteria);

				$this->service->execute($this->getRemoteApiEndpointId(), $input, $output);
				$response = $output->getData();
				if (empty($response['user'])) {
					$message = 'Failed to find user.';
					if ( ! empty($response['error'])) {
						$message .= $response['error'];
					}
					$logger->error($message);

					return;
				}

				$response = $response['user'];
				break;

			case Entity\Group::CN():
				$searchCriteria = array('command' => self::REMOTE_COMMAND_GROUP) + $searchCriteria;

				$input = new ArrayInput($searchCriteria);

				$this->service->execute($this->getRemoteApiEndpointId(), $input, $output);
				$response = $output->getData();
				if (empty($response['user'])) {
					$message = 'Failed to find user.';
					if ( ! empty($response['error'])) {
						$message .= $response['error'];
					}
					$logger->error($message);

					return;
				}

				$response = $response['user'];
				break;
		}

		return $response;
	}

	/**
	 * @return string
	 */
	public function getRemoteApiEndpointId()
	{
		return $this->remoteApiEndpointId;
	}

	/**
	 * @param string $remoteApiEndpointId 
	 */
	public function setRemoteApiEndpointId($remoteApiEndpointId)
	{
		$this->remoteApiEndpointId = $remoteApiEndpointId;
	}

	private function findUserByCriteria(array $criteria)
	{
		return $this->requestData(Entity\User::CN(), $criteria);
	}

	private function findGroupByCriteria(array $criteria)
	{
		return $this->requestData(Entity\Group::CN(), $criteria);
	}

}
