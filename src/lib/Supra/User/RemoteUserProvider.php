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
use Supra\Log\Writer\WriterAbstraction;

class RemoteUserProvider extends UserProviderAbstract
{

	/**
	 * @var RemoteCommandService
	 */
	private $remoteCommandService;

	/**
	 * @var string
	 */
	private $siteKey;

	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * @var string
	 */
	private $remoteApiEndpointId;

	const REMOTE_COMMAND_FIND_USER = 'su:portal:find_user';
	const REMOTE_COMMAND_FIND_GROUP = 'su:portal:find_group';
	const REMOTE_COMMAND_UPDATE_USER = 'su:portal:update_user';
	const REMOTE_COMMAND_CREATE_USER = 'su:portal:create_user';

	/**
	 * @return RemoteCommandService
	 */
	public function getRemoteCommandService()
	{
		if (empty($this->remoteCommandService)) {
			$this->remoteCommandService = new RemoteCommandService();
		}
		return $this->remoteCommandService;
	}

	/**
	 * @return WriterAbstraction
	 */
	protected function getLog()
	{
		if (empty($this->log)) {
			$this->log = ObjectRepository::getLogger($this);
		}

		return $this->log;
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
		return $this->findGroupByCriteria(array('--all-groups' => true));
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
		return $this->findUserByCriteria(array('field' => 'group', 'value' => $group->getId()));
	}

	/**
	 * {@inheritDoc}
	 */
	public function createUser()
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * {@inheritDoc}
	 */
	public function createGroup()
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * {@inheritDoc}
	 */
	public function doInsertUser(Entity\User $user)
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * {@inheritDoc}
	 */
	public function doInsertGroup(Entity\Group $group)
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * {@inheritDoc}
	 */
	public function doDeleteUser(Entity\User $user)
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateUser(Entity\User $user)
	{
		$parameters = array(
			'user' => $user->getId(),
			'--name' => $user->getName(),
			'--email' => $user->getEmail(),
			'--password_hash' => $user->getPassword(),
			'--avatar' => $user->getAvatar(),
			'--has_personal_avatar' => $user->hasPersonalAvatar()
		);

		$commandResponse = $this->executeSupraPortalCommand(self::REMOTE_COMMAND_UPDATE_USER, $parameters);

		if ( ! empty($commandResponse['error'])) {
			throw new Exception\RuntimeException('SupraPortal: ' . $commandResponse['error']);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateGroup(Entity\Group $group)
	{
		throw new \Exception('Not implemented. Need to rewrite SupraPortal User provider');
	}

	/**
	 * @param string $command
	 * @param array $parameters
	 * @return mixed 
	 */
	protected function executeSupraPortalCommand($command, $parameters)
	{
		$remoteApiEndpointId = $this->getRemoteApiEndpointId();

		if (empty($remoteApiEndpointId)) {

			$this->getLog()
					->error('Remote api endpoint id is not configured. Add [' . RemoteCommandService::INI_SECTION_NAME . '] section to supra.ini and then assign remote api endpoint id to ' . __CLASS__);

			throw new Exception\RuntiemException('Remote api endpoint id is not configured.');
		}

		$inputArray = array('command' => $command, 'site' => $this->getSiteKey()) + $parameters;

		$output = new ArrayOutputWithData();
		$input = new ArrayInput($inputArray);

		$this->getRemoteCommandService()
				->execute($this->getRemoteApiEndpointId(), $input, $output);

		$response = $output->getData();

		return $response;
	}

	/**
	 * @param string $entityName
	 * @param array $searchCriteria
	 */
	private function requestData($entityName, $searchCriteria = null)
	{
		if ( ! is_array($searchCriteria)) {
			$searchCriteria = array();
		}

		$response = null;

		switch ($entityName) {

			case Entity\User::CN():

				$command = self::REMOTE_COMMAND_FIND_USER;

				break;

			case Entity\Group::CN():

				$command = self::REMOTE_COMMAND_FIND_GROUP;

				break;

			default:

				throw new Exception\RuntimeException('Entities"' . $entityName . '" is not supproted.');
		}

		$commandResponse = $this->executeSupraPortalCommand($command, $searchCriteria);

		if (empty($commandResponse['data'])) {

			$errorMessage = 'Failed to find entity "' . $entityName . '". ';

			if ( ! empty($commandResponse['error'])) {
				$errorMessage .= $commandResponse['error'];
			}

			$this->getLog()
					->error($errorMessage, $searchCriteria);
		} else {
			$response = $commandResponse['data'];
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

	public function getSiteKey()
	{
		return $this->siteKey;
	}

	public function setSiteKey($siteKey)
	{
		$this->siteKey = $siteKey;
	}

	public function canUpdate()
	{
		return false;
	}

	public function canCreate()
	{
		return true;
	}

	public function loadUserByUsername($username)
	{
		
	}

	public function refreshUser(UserInterface $user)
	{
		
	}

	public function supportsClass($class)
	{
		
	}

}
