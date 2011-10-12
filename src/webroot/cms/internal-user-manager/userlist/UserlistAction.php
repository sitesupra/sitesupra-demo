<?php

namespace Supra\Cms\InternalUserManager\Userlist;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Doctrine\ORM\EntityRepository;
use Supra\Authorization\Exception\ConfigurationException as AuthorizationConfigurationException;
use Supra\ObjectRepository\ObjectRepository;

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
		$result = array();

		$groupRepository = $this->entityManager->getRepository(Entity\Group::CN());
		$groups = $groupRepository->findAll();
		
		/* @var $group Entity\Group */
		foreach($groups as $group) {
			
			$result[] = array(
				'id' => $group->getId(),
				'avatar' => null,
				'name' =>  '[[[' . $group->getName() . ']]]',
				'group' => $this->dummyGroupMap[$group->getName()]
			);
		}
		
		$users = $this->userRepository->findAll();
		
		/* @var $user Entity\User */
		foreach ($users as $user) {
			
			if( is_null($user->getGroup())) {
				
				\Log::debug('USER HAS NO GROUP: ', $user);
				
				continue;
			}
			
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
		$newGroupName = array_search($newGroupDummyId, $this->dummyGroupMap);
		
		/* @var $user Entity\User */
		$user = $this->userRepository->find($userId);
		
		if (empty($user)) {
			$this->getResponse()->setErrorMessage('User not found or is not a user but a group.');
			return;
		}
		
		if ($user->isSuper() && $user->getId() == $this->getUser()->getId()) {
			$this->getResponse()->setErrorMessage('You can not move Yourself out of SUPER group!');
			return;
		}
		
		/* @var $groupRepository EntityRepository */
		$groupRepository = $this->entityManager->getRepository(Entity\Group::CN());
		$newGroup = $groupRepository->findOneBy(array('name' => $newGroupName));
		
		if($user->getGroup()->getId() != $newGroup->getId()) {
			
			$ap = ObjectRepository::getAuthorizationProvider($this);
			
			$ap->unsetAllUserPermissions($user);
			
			$user->setGroup($newGroup);	
		}
		
		$this->entityManager->flush();
	}
}
