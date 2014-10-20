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
use Supra\Cms\Exception\CmsException;
use Supra\Cms\InternalUserManager\ApplicationConfiguration;
use Supra\Cms\InternalUserManager\Useravatar\UseravatarAction;

/**
 * Sitemap
 */
class UserlistAction extends InternalUserManagerAbstractAction
{
	/* @var EntityRepository */
	//private $userRepository;
	
	function __construct() 
	{
		parent::__construct();
		
		//$this->userRepository = $this->entityManager->getRepository(Entity\User::CN());
	}
	
	public function userlistAction()
	{

	}
	
	/**
	 * User update action
	 */
	public function updateAction() 
	{
		$this->isPostRequest();
		
		$userId = $this->getRequestParameter('user_id');
		$newGroupDummyId = $this->getRequestParameter('group');
		$newGroupName = $this->dummyGroupIdToGroupName($newGroupDummyId);
		
		/* @var $user Entity\User */
		//$user = $this->userRepository->find($userId);
		$user = $this->userProvider
				->findUserById($userId);
		
		if (empty($user)) {
			throw new CmsException(null, 'Requested user was not found');
		}
		
		if ($user->isSuper() && $user->getId() == $this->getUser()->getId()) {
			throw new CmsException(null, 'You cannot change group for yourself');
		}
		
		/* @var $groupRepository EntityRepository */
		//$groupRepository = $this->entityManager->getRepository(Entity\Group::CN());
		//$newGroup = $groupRepository->findOneBy(array('name' => $newGroupName));
		
		$newGroup = $this->userProvider
				->findGroupByName($newGroupName);
		
		// On user group change all user individual permissions are unset
		//TODO: ask confirmation from the action caller for this
		if($user->getGroup()->getId() != $newGroup->getId()) {
			$ap = ObjectRepository::getAuthorizationProvider($this);
			$ap->unsetAllUserPermissions($user);
			$user->setGroup($newGroup);	
		}
		
		$this->userProvider->credentialChange($user);
		$this->userProvider->updateUser($user);
		
		$this->writeAuditLog("User '" . $user->getName()
				. "' moved to group '" . $newGroupName ."'");
	}

	/**
	 * @param string $dummyId
	 * @return string
	 */
	protected function dummyGroupIdToGroupName($dummyId)
	{
		return $this->reverseDummyGroupMap[$dummyId];
	}

	/**
	 * @param Entity\Group $group
	 * @return string
	 */
	protected function groupToDummyId(Entity\Group $group)
	{
		return $this->dummyGroupMap[$group->getName()];
	}
}
