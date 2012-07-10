<?php

namespace Supra\Cms\InternalUserManager\User;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Exception;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Supra\User\Entity\AbstractUser;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\User\Entity\Group;
use Supra\Cms\Exception\CmsException;
use Supra\Cms\InternalUserManager\Useravatar\UseravatarAction;

class UserAction extends InternalUserManagerAbstractAction
{

	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat userAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function userAction()
	{
		$result = array();

		$this->getResponse()->setResponseData($result);
	}

	/**
	 * Loads user information
	 */
	public function loadAction()
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ($this->emptyRequestParameter('user_id')) {
			$this->getResponse()->setErrorMessage('User id is not set');
		}

		/* @var $user AbstractUser */
		$user = $this->getUserOrGroupFromRequestKey('user_id');

		if ($user->getId() != $this->getUser()->getId()) {
			$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);
		}

		$response = $this->getUserResponseArray($user);
		$response['permissions'] = $this->getApplicationPermissionsResponseArray($user);

		$response['canUpdate'] = $this->userProvider->canUpdate();
		$response['canDelete'] = $this->userProvider->canDelete();
		$response['canCreate'] = $this->userProvider->canCreate();

		$this->getResponse()->setResponseData($response);
	}

	/**
	 * Delete user action
	 */
	public function deleteAction()
	{
		$this->isPostRequest();

		if ( ! $this->userProvider->canDelete()) {
			throw new CmsException('This provider does not delete!');
		}

		$input = $this->getRequestInput();

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ($input->isEmpty('user_id')) {

			$this->getResponse()->setErrorMessage('User id is not set');
			return;
		}

		$userId = $input->get('user_id');

		$currentUser = $this->getUser();
		$currentUserId = $currentUser->getId();

		if ($currentUserId == $userId) {
			$this->getResponse()->setErrorMessage('You can\'t delete current user account');
			return;
		}

		$user = $this->userProvider->findUserById($userId);

		if (empty($user)) {
			$this->getResponse()->setErrorMessage('Can\'t find user with such id');
			return;
		}

		$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		//$entityManager = ObjectRepository::getEntityManager($this->userProvider);
		//$entityManager->remove($user);
		//$entityManager->flush();

		$this->userProvider
				->deleteUser($user);

		$this->writeAuditLog("User '" . $user->getName() . "' deleted");

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Password reset action
	 */
	public function resetAction()
	{
		$this->isPostRequest();

		if ( ! $this->userProvider->canUpdate()) {
			throw new CmsException('This provider does not update!');
		}

		$input = $this->getRequestInput();

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ($input->isEmpty('user_id')) {

			$this->getResponse()->setErrorMessage('User id is not set');
			return;
		}

		$userId = $input->get('user_id');

		/* @var $user Entity\User */
		$user = $this->userProvider->findUserById($userId);

		if (empty($user)) {

			$this->getResponse()->setErrorMessage('Can\'t find user with such id');
			return;
		}

		$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		$this->sendPasswordChangeLink($user);

		$this->writeAuditLog("Password for user '" . $user->getName() . "' reseted");

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Insert action
	 */
	public function insertAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "
		$email = $input->getValid('email', 'email');

		$existingUser = $this->userProvider->findUserByEmail($email);
		if ( ! empty($existingUser)) {
			throw new CmsException(null, 'User with this email is already registered!');
		}

		$name = $input->get('name');
		$dummyGroupId = $input->get('group');

		//$em = $this->userProvider->getEntityManager();

		$groupName = $this->dummyGroupIdToGroupName($dummyGroupId);
		$group = $this->userProvider->findGroupByName($groupName);

		$this->checkActionPermission($group, Group::PERMISSION_MODIFY_USER_NAME);

		$user = $this->userProvider
				->createUser();

		$user->setName($name);
		$user->setEmail($email);
		$user->setGroup($group);

		try {
			$this->userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			//FIXME: don't pass original message!
			throw new CmsException(null, "Not valid input: {$exc->getMessage()}");
		}

		$this->userProvider->credentialChange($user);
		$this->userProvider->insertUser($user);

		$this->writeAuditLog("User '" . $user->getName() . "' created");

		$this->getResponse()->setResponseData(array('user_id' => $user->getId()));
	}

	/**
	 * User save
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();

		// try to find as user/group ...
		$user = $this->getEntityFromRequestKey('user_id');

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "

		if ($user->getId() != $this->getUser()->getId()) {
			$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);
		}

		//TODO: temporary solution for groups, don't save anything
		if ( ! $user instanceof Entity\User) {
			$response = $this->getUserResponseArray($user);
			$this->getResponse()->setResponseData($response);

			return;
		}

		if ($input->has('name')) {
			$name = $input->get('name');
			$user->setName($name);
		}

		if ($input->has('email')) {
			$email = $input->getValid('email', 'email');
			$user->setEmail($email);
		}

		if ( ! $input->isEmpty('avatar_id', false)) {
			$avatar = $input->get('avatar_id');

			$predefinedAvatarIds = UseravatarAction::getPredefinedAvatarIds();
			if (in_array($avatar, $predefinedAvatarIds)) {
				$user->setAvatar($avatar);
				$user->setPersonalAvatar(false);
			}
		}

		try {
			$this->userProvider->validate($user);
		} catch (Exception\RuntimeException $e) {
			throw new CmsException(null, "Not valid input: {$e->getMessage()}");
		}

		$this->userProvider->credentialChange($user);
		$this->userProvider->updateUser($user);

		$this->writeAuditLog("User '" . $user->getName() . "' saved");

		$response = $this->getUserResponseArray($user);
		$this->getResponse()->setResponseData($response);
	}

}
