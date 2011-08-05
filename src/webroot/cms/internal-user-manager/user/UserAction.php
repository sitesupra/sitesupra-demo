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
	 * Sets error message to JsonResponse object
	 * @param string $message error message
	 */
	private function setErrorMessage($message)
	{
		$this->getResponse()->setErrorMessage($message);
		$this->getResponse()->setStatus(false);
	}
	

}
