<?php

namespace Supra\Cms\InternalUserManager\userlist;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerActionController;

use Supra\User\Entity;
use Supra\User\UserProvider;

/**
 * Sitemap
 */
class UserlistAction extends InternalUserManagerActionController
{

	public function userlistAction()
	{
		$userProvider = UserProvider::getInstance();
		$em = $userProvider->getEntityManager();
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $userProvider->getRepository();

		$users = $repo->findAll();
		
		$result = array();
		
		/* @var $user Entity\User */
		foreach ($users as $user) {
			
			$result[] = array(
				'id' => $user->getId(),
				'avatar' => null,
				'name' => $user->getName(),
				'group' => 1
			);
			
		}

		$this->getResponse()->setResponseData($result);
	}
	

}
