<?php

namespace Supra\Cms\InternalUserManager\User;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Exception;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Supra\User\Entity\Abstraction\User;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\Mailer\Message\TwigMessage;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\User\Entity\Group as RealGroup;
use Supra\Cms\Exception\CmsException;

/**
 * Sitemap
 */
class UserAction extends InternalUserManagerAbstractAction
{

	/**
	 * @var AuthorizationProvider
	 */
	private $authorizationProvider;

	function __construct()
	{

		parent::__construct();

		$this->authorizationProvider = ObjectRepository::getAuthorizationProvider($this);
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

		/* @var $user User */
		$user = $this->getUserOrGroupFromRequestKey('user_id');

		if ($user->getId() != $this->getUser()->getId()) {
			$this->checkActionPermission($user->getGroup(), RealGroup::PERMISSION_MODIFY_USER_NAME);
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
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ($this->emptyRequestParameter('user_id')) {

			$this->getResponse()->setErrorMessage('User id is not set');
			return;
		}

		$userId = $this->getRequestParameter('user_id');

		$session = ObjectRepository::getSessionNamespace($this);
		$currentUser = $session->getUser();
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

		$this->checkActionPermission($user->getGroup(), RealGroup::PERMISSION_MODIFY_USER_NAME);

		$this->userProvider->getEntityManager()->remove($user);
		$this->userProvider->getEntityManager()->flush();

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Password reset action
	 */
	public function resetAction()
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$this->getResponse()->setErrorMessage('User id is not set');
			return;
		}

		$userId = $this->getRequestParameter('user_id');

		/* @var $user Entity\User */
		$user = $this->userProvider->findUserById($userId);

		if (empty($user)) {

			$this->getResponse()->setErrorMessage('Can\'t find user with such id');
			return;
		}

		$this->checkActionPermission($user->getGroup(), RealGroup::PERMISSION_MODIFY_USER_NAME);

		$expTime = time();
		$userMail = $user->getEmail();
		$hash = $this->generateHash($user, $expTime);

		// TODO: Change hardcoded link
		$host = $this->request->getServerValue('HTTP_HOST');
		$url = 'http://' . $host . '/cms/internal-user-manager/restore';
		$query = http_build_query(array(
				'e' => $userMail,
				't' => $expTime,
				'h' => $hash,
				));

		$mailVars = array(
				'link' => $url . '?' . $query
		);

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();
		$message->setTemplatePath(__DIR__ . '/mail');
		// FIXME: from address should not be hardcoded here etc.
		$message->setSubject('Password recovery')
				->setFrom('admin@supra7.vig')
				->setTo($userMail)
				->setBody('resetpassword.twig', $mailVars);
		$mailer->send($message);

		$this->getResponse()->setResponseData(null);
	}

	public function insertAction()
	{
		$this->isPostRequest();

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "
		if ( ! $this->emptyRequestParameter('email') &&
				! $this->emptyRequestParameter('name') &&
				! $this->emptyRequestParameter('group')) {

			$email = $this->getRequestParameter('email');
			$name = $this->getRequestParameter('name');
			$dummyGroupId = $this->getRequestParameter('group');

			$em = $this->userProvider->getEntityManager();

			$groupName = array_search($dummyGroupId, $this->dummyGroupMap);
			$group = $this->userProvider->findGroupByName($groupName);

			$this->checkActionPermission($group, RealGroup::PERMISSION_MODIFY_USER_NAME);

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
				$this->getResponse()->setErrorMessage($exc->getMessage());
				return;
			}

			$authAdapter = $this->userProvider->getAuthAdapter();
			$authAdapter->credentialChange($user);

			$expTime = time();
			$userMail = $user->getEmail();
			$hash = $this->generateHash($user, $expTime);

			// TODO: Change hardcoded link
			$host = $this->request->getServerValue('HTTP_HOST');
			$url = 'http://' . $host . '/cms/internal-user-manager/restore';
			$query = http_build_query(array(
					'e' => $userMail,
					't' => $expTime,
					'h' => $hash,
					));

			$mailVars = array(
					'link' => $url . '?' . $query
			);

			$mailer = ObjectRepository::getMailer($this);
			$message = new TwigMessage();
			$message->setTemplatePath(__DIR__ . '/mail');
			// FIXME: from address should not be hardcoded here etc.
			$message->setSubject('Account created. Set your password')
					->setFrom('admin@supra7.vig')
					->setTo($userMail)
					->setBody('createpassword.twig', $mailVars);
			$mailer->send($message);

			$em->flush();

			$response = array(
					'name' => $name,
					'avatar' => '/cms/lib/supra/img/avatar-default-32x32.png',
					'email' => $email,
					'group' => $dummyGroupId,
					'user_id' => $user->getId(),
			);

			$this->getResponse()->setResponseData($response);
		}
		else {

			//error message
		}
	}

	public function saveAction()
	{
		$this->isPostRequest();

		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "
		if ( ! $this->emptyRequestParameter('user_id') &&
				! $this->emptyRequestParameter('email') &&
				! $this->emptyRequestParameter('group') &&
				! $this->emptyRequestParameter('name')) {

			$email = $this->getRequestParameter('email');
			$name = $this->getRequestParameter('name');
			$group = $this->getRequestParameter('group');
			$userId = $this->getRequestParameter('user_id');


			// try to find as user/group ...
			$user = $this->userProvider->findById($userId);

			// ... if not found, bail out.
			if (empty($user)) {

				$this->getResponse()->setErrorMessage('User with such id doesn\'t exist');
				return;
			}

			if ($user->getId() != $this->getUser()->getId()) {
				$this->checkActionPermission($user->getGroup(), RealGroup::PERMISSION_MODIFY_USER_NAME);
			}

			// temporary solution when save action is triggered and there are no changes
			if (
					($user instanceof Entity\User) && (
					($email == $user->getEmail()) &&
					($name == $user->getName())
					) ||
					($user instanceof Entity\Group)
			) {

				$response = $this->getUserResponseArray($user);

				$this->getResponse()->setResponseData($response);

				return;
			}

			// TODO: add group and avatar
			$user->setName($name);
			$user->setEmail($email);

			try {
				$this->userProvider->validate($user);
			}
			catch (Exception\RuntimeException $e) {

				$this->getResponse()->setErrorMessage($e->getMessage());
				return;
			}

			$authAdapter = $this->userProvider->getAuthAdapter();
			$authAdapter->credentialChange($user);

			$this->entityManager->flush();

			$response = $this->getUserResponseArray($user);

			$this->getResponse()->setResponseData($response);
		}
		else {
			// error message
		}
	}

	/**
	 * Returns array for response 
	 * @param User $user
	 * @return array
	 */
	private function getUserResponseArray(User $user)
	{
		$response = array(
				'name' => $user->getName(),
				'avatar' => '/cms/lib/supra/img/avatar-default-32x32.png',
				'user_id' => $user->getId()
		);

		if ($user instanceof Entity\User) {

			$response['email'] = $user->getEmail();
			$response['group'] = $this->dummyGroupMap[$user->getGroup()->getName()];
		}
		else {

			$response['email'] = 'N/A';
			$response['group'] = $this->dummyGroupMap[$user->getName()];
		}

		return $response;
	}

	/**
	 * Generates hash for password recovery
	 * @param Entity\User $user 
	 */
	private function generateHash(Entity\User $user, $expirationTime)
	{
		$salt = $user->getSalt();
		$email = $user->getEmail();

		$hash = sha1($expirationTime . $salt . $email);

		return $hash;
	}

}
