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

/**
 * Sitemap
 */
class UserAction extends InternalUserManagerAbstractAction
{
	function __construct()
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

		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();

		$permissions = array();

		foreach ($appConfigs as $appConfig) {
			/* @var $appConfig ApplicationConfiguration  */

			$permissions[$appConfig->id] = $appConfig->authorizationAccessPolicy->getAccessPolicy($user);
		}

		$response = $this->getUserResponseArray($user);
		$response['permissions'] = $permissions;

		$this->getResponse()->setResponseData($response);
	}

	/**
	 * Delete user action
	 */
	public function deleteAction()
	{
		$this->isPostRequest();
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

		$entityManager = ObjectRepository::getEntityManager($this->userProvider);
		
		$entityManager->remove($user);
		$entityManager->flush();

		$this->writeAuditLog('delete user', 
				"User '" . $user->getName() . "' deleted");

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Password reset action
	 */
	public function resetAction()
	{
		$this->isPostRequest();
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

		$this->writeAuditLog('reset password', 
				"Password for user '" . $user->getName() . "' reseted");

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
		$name = $input->get('name');
		$dummyGroupId = $input->get('group');

		$em = $this->userProvider->getEntityManager();

		$groupName = array_search($dummyGroupId, $this->dummyGroupMap);
		$group = $this->userProvider->findGroupByName($groupName);

		$this->checkActionPermission($group, Group::PERMISSION_MODIFY_USER_NAME);

		$user = new Entity\User();
		$em->persist($user);

		// TODO: add group, avatar
		$user->setName($name);
		$user->setEmail($email);

		$user->setGroup($group);

		try {
			$this->userProvider->validate($user);
		}
		catch (Exception\RuntimeException $exc) {
			//FIXME: don't pass original message!
			throw new CmsException(null, "Not valid input: {$exc->getMessage()}");
		}

		$authAdapter = $this->userProvider->getAuthAdapter();
		$authAdapter->credentialChange($user);

		$this->sendPasswordChangeLink($user, 'createpassword');

		$this->writeAuditLog('insert user', 
				"User '" . $user->getName() . "' created");
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
		
		if ($input->has('avatar')) {
			$avatar = $input->get('avatar');
			$user->setAvatar($avatar);
		}

		try {
			$this->userProvider->validate($user);
		}
		catch (Exception\RuntimeException $e) {
			throw new CmsException(null, "Not valid input: {$e->getMessage()}");
		}

		$authAdapter = $this->userProvider->getAuthAdapter();
		$authAdapter->credentialChange($user);

		$this->writeAuditLog('save user', 
				"User '" . $user->getName() . "' saved");

		$response = $this->getUserResponseArray($user);
		$this->getResponse()->setResponseData($response);
	}

	/**
	 * Returns array for response 
	 * @param AbstractUser $user
	 * @return array
	 */
	private function getUserResponseArray(AbstractUser $user)
	{
		$response = array(
			'user_id' => $user->getId(),
			'name' => $user->getName()
		);

		if ($user instanceof Entity\User) {
			$response['email'] = $user->getEmail();
			$response['group'] = $this->dummyGroupMap[$user->getGroup()->getName()];
			$response['avatar'] = $user->getAvatar();
		}
		else {
			$response['email'] = 'N/A';
			$response['group'] = $this->dummyGroupMap[$user->getName()];
		}
		
		if (empty($response['avatar'])) {
			$response['avatar'] = '/cms/lib/supra/img/avatar-default-48x48.png';
		}

		return $response;
	}

}
