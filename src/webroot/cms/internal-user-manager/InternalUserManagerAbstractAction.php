<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Cms\CmsAction;
use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\User\UserProvider;
use Doctrine\ORM\EntityManager;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\Mailer\Message\TwigMessage;

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
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $dummyGroupMap;

	/**
	 * Bind objects
	 */
	public function __construct()
	{
		parent::__construct();

		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->entityManager = $this->userProvider->getEntityManager();

		//TODO: implement normal group loader and IDs
		$this->dummyGroupMap = array('admins' => 1, 'contribs' => 3, 'supers' => 2);
	}

	protected function getRequestedEntity($key, $className)
	{
		if ( ! $this->hasRequestParameter($key)) {
			throw new CmsException('internalusermanager.validation_error.user_id_not_provided');
		}

		$id = $this->getRequestParameter($key);
		$user = $this->entityManager->find($className, $id);

		if (is_null($user)) {
			throw new CmsException('internalusermanager.validation_error.user_not_exists');
		}

		return $user;
	}

	/**
	 * @return Entity\AbstractUser
	 */
	protected function getEntityFromRequestKey($key = 'id')
	{
		$user = $this->getRequestedEntity($key, Entity\AbstractUser::CN());

		return $user;
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
		}
		catch (CmsException $e) {

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
	 */
	protected function sendPasswordChangeLink(Entity\User $user)
	{
		$time = time();
		$userMail = $user->getEmail();
		$hash = $this->generatePasswordRecoveryHash($user, $time);

		// TODO: Change hardcoded link
		$host = $this->request->getServerValue('HTTP_HOST');
		$url = 'http://' . $host . '/cms/restore';
		$query = http_build_query(array(
				'e' => $userMail,
				't' => $time,
				'h' => $hash,
				));

		$mailVars = array(
				'link' => $url . '?' . $query
		);

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();
		
		// FIXME: relative path
		$message->setTemplatePath(__DIR__ . '/mail-template');
		
		// FIXME: from address should not be hardcoded here etc.
		$message->setSubject('Password recovery')
				->setFrom('admin@supra7.vig')
				->setTo($userMail)
				->setBody('resetpassword.twig', $mailVars);
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

}