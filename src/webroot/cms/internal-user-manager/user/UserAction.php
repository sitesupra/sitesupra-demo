<?php

namespace Supra\Cms\InternalUserManager\User;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerActionController;
use Supra\User\Exception;
use Supra\User\Entity;
use Supra\User\UserProvider;

/**
 * Sitemap
 */
class UserAction extends InternalUserManagerActionController
{

	const SALT = '2j*s@;0?0saASf1%^&1!';

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

		if (isset($_GET['user_id'])) {

			$userId = $_GET['user_id'];

			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->setErrorMessage('Can\'t find user with such id');
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
			$this->setErrorMessage('User id is not set');
		}
	}

	/**
	 * Delete user action
	 */
	public function deleteAction()
	{

		if (isset($_GET['user_id'])) {

			$userId = $_GET['user_id'];

			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->setErrorMessage('Can\'t find user with such id');
				return;
			}

			$em->remove($user);
			$em->flush();

			$this->getResponse()->setResponseData(null);
		} else {
			$this->setErrorMessage('User id is not set');
		}
	}

	/**
	 * Password reset action
	 */
	public function resetAction()
	{

		if (isset($_GET['user_id'])) {

			$userId = $_GET['user_id'];

			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->setErrorMessage('Can\'t find user with such id');
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
			$this->setErrorMessage('User id is not set');
		}
	}

	public function insertAction()
	{
		if (isset($_POST['email'], $_POST['name'], $_POST['group'])) {

			$email = $_POST['email'];
			$name = $_POST['name'];
			$group = $_POST['group'];

			$userProvider = UserProvider::getInstance();

			$em = $userProvider->getEntityManager();

			$user = new Entity\User();

			$em->persist($user);

			$timeNow = new \DateTime('now');

			// TODO: add group and avatar
			$user->setName($name);
			$user->setPassword(md5($name . 'Norris' . self::SALT));
			$user->setEmail($email);
			$user->setCreatedTime($timeNow);
			$user->setModifiedTime($timeNow);

			try {
				$userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
				$this->setErrorMessage($exc->getMessage());
				return;
			}


			$em->flush();

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

		if (isset($_POST['user_id'])) {

			$email = $_POST['email'];
			$name = $_POST['name'];
			$group = $_POST['group'];
			$userId = $_POST['user_id'];

			$userProvider = UserProvider::getInstance();

			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);

			if (empty($user)) {
				$this->setErrorMessage('User with such id doesn\'t exists');

				return;
			}

			$em->persist($user);

			$timeNow = new \DateTime('now');

			// TODO: add group and avatar
			$user->setName($name);
			$user->setEmail($email);
			$user->setModifiedTime($timeNow);

			try {
				$userProvider->validate($user);
			} catch (Exception\RuntimeException $exc) {
				$this->setErrorMessage($exc->getMessage());
				return;
			}


			$em->flush();

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
	 * Sets error message to JsonResponse object
	 * @param string $message error message
	 */
	private function setErrorMessage($message)
	{
		$this->getResponse()->setErrorMessage($message);
		$this->getResponse()->setStatus(false);
	}

}
