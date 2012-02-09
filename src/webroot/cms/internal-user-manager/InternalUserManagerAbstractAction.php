<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Cms\CmsAction;
use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\User\UserProvider;
//use Doctrine\ORM\EntityManager;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\Mailer\Message\TwigMessage;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Cms\InternalUserManager\Useravatar\UseravatarAction;

/**
 * Internal user manager action controller
 * @method JsonResponse getResponse()
 */
class InternalUserManagerAbstractAction extends CmsAction
{

	/**
	 * @var UserProvider
	 */
	protected $userProvider;

	/**
	 * @var EntityManager
	 */
	//protected $entityManager;

	/**
	 * @var array
	 */
	protected $dummyGroupMap;
	protected $reverseDummyGroupMap;

	/**
	 * Bind objects
	 */
	public function __construct()
	{
		parent::__construct();

		$this->userProvider = ObjectRepository::getUserProvider($this);
		//$this->entityManager = $this->userProvider->getEntityManager();

		//TODO: implement normal group loader and IDs
		$this->dummyGroupMap = array('admins' => 1, 'contribs' => 3, 'supers' => 2);
		$this->reverseDummyGroupMap = array_flip($this->dummyGroupMap);
	}

	/**
	 * @param string $dummyId
	 * @return string
	 */
	protected function dummyGroupIdToGroupName($dummyId)
	{
		return $this->reverseDummyGroupMap[$dummyId];
	}

	/**
	 * @param Entity\Group $group
	 * @return string
	 */
	protected function groupToDummyId(Entity\Group $group)
	{
		return $this->dummyGroupMap[$group->getName()];
	}

	/**
	 * @param string $key
	 * @param string $className
	 * @return Entity\AbstractUser
	 */
	private function getRequestedEntity($key, $className)
	{
		$input = $this->getRequestInput();

		if ($input->isEmpty($key)) {
			throw new CmsException('internalusermanager.validation_error.user_id_not_provided');
		}

		$id = $input->get($key);
		
		switch($className) {
			case Entity\Group::CN():
				$entity = $this->userProvider->findGroupById($id);
				break;
			case Entity\User::CN():
				$entity = $this->userProvider->findUserById($id);
				break;
			
			case Entity\AbstractUser::CN():
				$entity = $this->userProvider->findUserById($id);
				if (is_null($entity)) {
					$entity = $this->userProvider->findGroupById($id);
				}
				break;
			default:
				throw new \Supra\User\Exception\RuntimeException('Incorrect entity was requested');
		}
		//$entity = $this->userProvider->findById($id);
		
		if ( ! $entity instanceof $className) {
			throw new CmsException('internalusermanager.validation_error.user_not_exists');
		}

		return $entity;
	}

	/**
	 * @return Entity\AbstractUser
	 */
	protected function getEntityFromRequestKey($key = 'id')
	{
		$entity = $this->getRequestedEntity($key, Entity\AbstractUser::CN());

		return $entity;
	}

	/**
	 * @return Entity\User
	 */
	protected function getUserFromRequestKey($key = 'id')
	{
		$user = $this->getRequestedEntity($key, Entity\User::CN());

		return $user;
	}

	/**
	 * @return Entity\Group
	 */
	protected function getGroupFromRequestKey($key = 'id')
	{
		$group = $this->getRequestedEntity($key, Entity\Group::CN());

		return $group;
	}

	protected function getUserOrGroupFromRequestKey($key)
	{
		$user = null;

		try {
			$user = $this->getUserFromRequestKey($key);
		} catch (CmsException $e) {

			$user = $this->getGroupFromRequestKey($key);

			if (empty($user)) {
				throw new CmsException('Can\'t find user or group with requested id.');
			}
		}

		return $user;
	}

	/**
	 * Sends password change link
	 * @param Entity\User $user
	 * @param string $template Template name from /cms/internal-user-manager/mail-template. By default resetpassword template is set
	 */
	public function sendPasswordChangeLink(Entity\User $user, $template = null)
	{
		$subject = 'New user account created';
		if (is_null($template)) {
			$template = 'resetpassword';
			$subject = 'Password recovery';
		}

		$time = time();
		$userMail = $user->getEmail();
		$hash = $this->generatePasswordRecoveryHash($user, $time);

		$authAdapter = ObjectRepository::getUserProvider($this)->getAuthAdapter();

		$userLogin = null;

		if (is_callable(array($authAdapter, 'getDefaultDomain'))) {
			$domain = $authAdapter->getDefaultDomain();
			if (strpos($userMail, '@' . $domain) && ! empty($domain)) {
				$emailParts = explode('@', $userMail);
				$userLogin = $emailParts[0];
			}
		}

		$systemInfo = ObjectRepository::getSystemInfo($this);
		$host = $systemInfo->getHostName(\Supra\Info::WITH_SCHEME);
		
		$url = $host . '/cms/restore';
		$query = http_build_query(array(
			'e' => $userMail,
			't' => $time,
			'h' => $hash,
				));

		$mailVars = array(
			'link' => $url . '?' . $query,
			'email' => $userMail,
			'login' => $userLogin,
		);

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();

		$message->setContext(__CLASS__);

		// FIXME: from address should not be hardcoded here etc.
		$message->setSubject($subject)
				->setTo($userMail)
				->setBody("mail-template/{$template}.twig", $mailVars);
		$mailer->send($message);
	}

	/**
	 * Generates hash for password recovery
	 * @param Entity\User $user 
	 * @return string
	 */
	protected function generatePasswordRecoveryHash(Entity\User $user, $time)
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
	 * @param Entity\AbstractUser $user
	 * @return array
	 */
	protected function getApplicationPermissionsResponseArray(Entity\AbstractUser $user)
	{
		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();

		$permissions = array();

		foreach ($appConfigs as $appConfig) {
			/* @var $appConfig ApplicationConfiguration  */

			$permissions[$appConfig->id] = $appConfig->authorizationAccessPolicy->getAccessPolicy($user);
		}

		return $permissions;
	}

	/**
	 * Returns array for response 
	 * @param AbstractUser $user
	 * @return array
	 */
	protected function getUserResponseArray(Entity\AbstractUser $user)
	{
		$response = array(
			'user_id' => $user->getId(),
			'name' => $user->getName(),
		);

		if ($user instanceof Entity\User) {
			
			$response['email'] = $user->getEmail();
			$response['group'] = $this->groupToDummyId($user->getGroup());
			
			if ( ! $user->hasPersonalAvatar()) {
				$response['avatar_id'] = $user->getAvatar();
			}
			
			$response['avatar'] = UseravatarAction::getAvatarExternalPath($user, '48x48');
		} else {
			
			$response['email'] = 'N/A';
			$response['group'] = $this->groupToDummyId($user);
			$response['group_id'] = $this->groupToDummyId($user);
		}

		if (empty($response['avatar'])) {
			$response['avatar'] = '/cms/lib/supra/img/avatar-default-48x48.png';
		}

		return $response;
	}

}