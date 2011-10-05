<?php

namespace Supra\Cms\InternalUserManager\Userlist;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Doctrine\ORM\EntityRepository;

/**
 * Sitemap
 */
class UserlistAction extends InternalUserManagerAbstractAction
{
	/* @var EntityRepository */
	private $userRepository;
	
	function __construct() 
	{
		parent::__construct();
		
		$this->userRepository = $this->entityManager->getRepository(Entity\User::CN());
	}
	
	public function userlistAction()
	{
		$users = $this->userRepository->findAll();
		
		$result = array();
		
		/* @var $user Entity\User */
		foreach ($users as $user) {
			
			$result[] = array(
				'id' => $user->getId(),
				'avatar' => null,
				'name' => $user->getName(),
				'group' => $this->dummyGroupMap[$user->getGroup()->getName()]
			);
			
		}

		$this->getResponse()->setResponseData($result);
	}
	
	public function updateAction() 
	{
		$this->isPostRequest();
		
		$userId = $this->getRequest()->getPostValue('user_id');
		$newGroupDummyId = $this->getRequest()->getPostValue('group');
		
		/* @var $user Entity\User */
		$user = $this->userRepository->find($userId);
		
		/* @var $groupRepository EntityRepository */
		$groupRepository = $this->entityManager->getRepository(Entity\Group::CN());
		
		$newGroupName = array_search($newGroupDummyId, $this->dummyGroupMap);
		$newGroup = $groupRepository->findOneBy(array('name' => $newGroupName));
		
		$user->setGroup($newGroup);
		
		$this->entityManager->flush();
	}
}
