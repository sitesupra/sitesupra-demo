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
		// TODO: Add validation class to have ability check like " if(empty($validation['errors'])){} "		
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

			// TODO:  Add mailer
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
			$link = $url . '?' . $query;

			$subject = 'Password recovery';
			$message = 'Go to ' . $link;

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

			// TODO: add group, avatar, password creation
			$user->setName($name);
			$user->setSalt();
			$password = $this->userProvider
							->generatePasswordHash('', $user->getSalt());
			$user->setPassword($password);
			$user->setEmail($email);

			try {
				$this->userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
				$this->getResponse()->setErrorMessage($exc->getMessage());
				return;
			}
			
			// TODO:  Add mailer
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
			$link = $url . '?' . $query;

			$subject = 'Account created. Set your password';
			$message = 'Your account is created. Go to ' . $link . ' to set your password.';

			$headers = 'From: admin@supra7.vig' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

			mail($userMail, $subject, $message, $headers);
			
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
