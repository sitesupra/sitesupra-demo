<?php

namespace Supra\Cms\InternalUserManager\User;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Supra\User\Exception;
use Supra\User\Entity;
use Supra\User\UserProvider;

/**
 * Sitemap
 */
class UserAction extends InternalUserManagerAbstractAction
{

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

		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');

			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->getResponse()
						->setErrorMessage('Can\'t find user with such id');
				return;
			}

			$response = array(
				'user_id' => $user->getId(),
				'name' => $user->getName(),
				'email' => $user->getEmail(),
				'avatar' => null,
				'group' => 1
			);

			$this->getResponse()->setResponseData($response);
		} else {
			$this->getResponse()
					->setErrorMessage('User id is not set');
		}
	}

	/**
	 * Delete user action
	 */
	public function deleteAction()
	{

		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');
			
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');;

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
		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "		
		if ( ! $this->emptyRequestParameter('user_id')) {

			$userId = $this->getRequestParameter('user_id');

			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $this->entityManager->getRepository('Supra\User\Entity\User');

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->getResponse()->setErrorMessage('Can\'t find user with such id');
				return;
			}

			// TODO: Change password to temporary
			// FIXME: Add mailer and real recovery link.
			$userMail = $user->getEmail();
			$subject = 'Password recovery';
			$message = 'Message and you our recovery link will be here';

			$headers = 'From: admin@supra7.vig' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

			mail($userMail, $subject, $message, $headers);

			$this->getResponse()->setResponseData(null);
		} else {
			$this->getResponse()->setErrorMessage('User id is not set');
		}
	}

	public function insertAction()
	{
		$this->isPostRequest();

		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "
		if ( ! $this->emptyRequestParameter('email') &&
				! $this->emptyRequestParameter('name') &&
				! $this->emptyRequestParameter('group')) {

			$email = $this->getRequestParameter('email');
			$name = $this->getRequestParameter('name');
			$group = $this->getRequestParameter('group');

			$user = new Entity\User();

			$this->entityManager->persist($user);

			// TODO: add group and avatar
			$user->setName($name);
			$user->setSalt();
			$user->setPassword(sha1($name . $user->getSalt()));
			$user->setEmail($email);

			try {
				$this->userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
				$this->getResponse()->setErrorMessage($exc->getMessage());
				return;
			}

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

		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "
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
			if(($email == $user->getEmail()) &&	($name == $user->getName())){
				
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

}
