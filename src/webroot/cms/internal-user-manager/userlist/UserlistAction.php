<?php

namespace Supra\Cms\InternalUserManager\Userlist;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity;
use Supra\User\UserProvider;

/**
 * Sitemap
 */
class UserlistAction extends InternalUserManagerAbstractAction
{

	public function userlistAction()
	{
		$repo = $this->entityManager->getRepository('Supra\User\Entity\User');
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
