<?php

namespace Supra\Cms\InternalUserManager\user;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerActionController;

use Supra\User\Entity;
use Supra\User\UserProvider;

/**
 * Sitemap
 */
class UserAction extends InternalUserManagerActionController
{

	public function userAction()
	{
		$result = array();

		$this->getResponse()->setResponseData($result);
	}
	
	/**
	 * Loads user information
	 */
	public function loadAction(){
		
		if(isset($_GET['user_id'])) {
			
			$userId = $_GET['user_id'];
			
			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);
			
			if(empty($user)) {
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
	public function deleteAction(){
		
		if(isset($_GET['user_id'])) {
			
			$userId = $_GET['user_id'];
			
			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);
			
			if(empty($user)) {
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
	public function resetAction(){
		
		if(isset($_GET['user_id'])) {
			
			$userId = $_GET['user_id'];
			
			$userProvider = UserProvider::getInstance();
			$em = $userProvider->getEntityManager();
			/* @var $repo Doctrine\ORM\EntityRepository */
			$repo = $userProvider->getRepository();

			$user = $repo->findOneById($userId);
			
			if(empty($user)) {
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
