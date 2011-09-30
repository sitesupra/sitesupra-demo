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
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;
/**
 * Sitemap
 */
class UserAction extends InternalUserManagerAbstractAction
{
	/**
	 * @var AuthorizationProvider
	 */
	private $authorizationProvider;
	
	function __construct() {
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
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');

			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');

			$user = $repo->findOneById($userId);
			/* @var $user User */

			if (empty($user)) {
				$this->getResponse()
						->setErrorMessage('Can\'t find user with such id');
				return;
			}
			
			$config = CmsApplicationConfiguration::getInstance();
			$appConfigs = $config->getArray();
			foreach ($appConfigs as $appConfig) {
				/* @var $appConfig ApplicationConfiguration  */
				
				$permission = array();
				
				$permission["allow"] = $appConfig->authorizationAccessPolicy->getAccessPermission($user);

				if($appConfig->authorizationAccessPolicy instanceof AuthorizationThreewayAccessPolicy) {
					
					$permission["items"] = $appConfig->authorizationAccessPolicy->getItemPermissions($user);
				}
						
				$permissions[$appConfig->id] = $permission;
				
			}

			$response = array(
				'user_id' => $user->getId(),
				'name' => $user->getName(),
				'email' => $user->getEmail(),
				'avatar' => null,
				'group' => 1,
				'permissions' => $permissions
			);

			$this->getResponse()->setResponseData($response);
		} 
		else {
			$this->getResponse()->setErrorMessage('User id is not set');
		}
	}
	
	/**
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return integer
	 */
	function getApplicationAccess($user, ApplicationConfiguration $applicationConfiguration)
	{
		if ($applicationConfiguration->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			return $this->authorizationProvider->getAccessPermission($user);
		}
		else {
			return "0";
		}
	}

	/**
	 * Delete user action
	 */
	public function deleteAction()
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');
			$currentUser = $_SESSION['user'];
			$currentUserId = $currentUser->getId();
		
			if ($currentUserId == $userId) {
				$this->getResponse()->setErrorMessage('You can\'t delete current user account');
				return;
			}
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->getResponse()->setErrorMessage('Can\'t find user with such id');
				return;
			}

			$this->entityManager->remove($user);
			$this->entityManager->flush();

			$this->getResponse()->setResponseData(null);
		} else {
			$this->getResponse()->setErrorMessage('User id is not set');
		}
	}

	/**
	 * Password reset action
	 */
	public function resetAction()
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');

			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');
			/* @var $user Entity\User */
			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->getResponse()->setErrorMessage('Can\'t find user with such id');
				return;
			}

			$expTime = time();
			$userMail = $user->getEmail();
			$hash = $this->generateHash($user, $expTime);

			// TODO: Change hardcoded link
			$host = $this->request->getServerValue('HTTP_HOST');
			$url = 'http://'. $host .'/cms/internal-user-manager/restore';
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
		} else {
			$this->getResponse()->setErrorMessage('User id is not set');
		}
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
			$group = $this->getRequestParameter('group');

			$user = new Entity\User();

			$this->entityManager->persist($user);

			// TODO: add group, avatar
			$user->setName($name);
			$user->setEmail($email);
			
			try {
				$this->userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
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
			$url = 'http://'. $host .'/cms/internal-user-manager/restore';
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
			
			$this->entityManager->flush();

			$response = array(
				'name' => $name,
				'avatar' => '/cms/lib/supra/img/avatar-default-32x32.png',
				'email' => $email,
				'group' => 1,
				'user_id' => $user->getId(),
			);

			$this->getResponse()->setResponseData($response);
		} else {

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

			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');

			$user = $repo->findOneById($userId);

			// temporary solution when save action is triggered and there is no changes
			if (($email == $user->getEmail()) && ($name == $user->getName())) {

				$response = array(
					'name' => $name,
					'avatar' => '/cms/lib/supra/img/avatar-default-32x32.png',
					'email' => $email,
					'group' => 1,
					'user_id' => $userId,
				);
				$this->getResponse()->setResponseData($response);
				return;
			}

			if (empty($user)) {
				$this->getResponse()->setErrorMessage('User with such id doesn\'t exists');

				return;
			}

			$this->entityManager->persist($user);

			// TODO: add group and avatar
			$user->setName($name);
			$user->setEmail($email);

			try {
				$this->userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
				$this->getResponse()->setErrorMessage($exc->getMessage());
				return;
			}

			$authAdapter = $this->userProvider->getAuthAdapter();
			$authAdapter->credentialChange($user);

			$this->entityManager->flush();

			$response = array(
				'name' => $name,
				'avatar' => '/cms/lib/supra/img/avatar-default-32x32.png',
				'email' => $email,
				'group' => 1,
				'user_id' => $userId,
			);

			$this->getResponse()->setResponseData($response);
		} else {
			// error message
		}
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
