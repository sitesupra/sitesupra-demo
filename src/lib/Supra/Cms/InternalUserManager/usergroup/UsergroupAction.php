<?php

namespace Supra\Cms\InternalUserManager\Usergroup;

use Supra\Cms\InternalUserManager\User\UserAction;
use Supra\User\Entity\AbstractUser;
use Supra\User\UserProvider;
use Supra\User\Entity\Group;

class UsergroupAction extends UserAction
{
	/**
	 * @param string $key
	 * @return AbstractUser
	 */
	protected function getGroupFromDummyGroupId($dummyGroupId)
	{
		$groupName = $this->dummyGroupIdToGroupName($dummyGroupId);
		
		/* @var $group AbstractUser */
		$group = $this->userProvider->findGroupByName($groupName);
		
		return $group;
	}

	public function loadAction()
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "		
		if ($this->emptyRequestParameter('group_id')) {
			$this->getResponse()->setErrorMessage('User id is not set');
		}

		$dummyGroupId = $this->getRequest()
				->getParameter('group_id');
		
		$group = $this->getGroupFromDummyGroupId($dummyGroupId);
		
		$this->checkActionPermission($group->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);
		
		$response = $this->getUserResponseArray($group);
		$response['permissions'] = $this->getApplicationPermissionsResponseArray($group);
		
		$this->getResponse()->setResponseData($response);
		
	}

}
