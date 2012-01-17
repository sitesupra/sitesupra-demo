<?php

namespace Supra\Cms\InternalUserManager\Permissionproperties;

use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity as PageEntity;
use Supra\FileStorage\Entity as FileEntity;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\Locale\LocaleManager;
use Supra\Cms\ApplicationConfiguration;
use Supra\User\Entity\Group;
use Supra\Authorization\Exception\ConfigurationException as AuthorizationConfigurationException;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\User\UserProvider;

class PermissionpropertiesAction extends InternalUserManagerAbstractAction
{

	public function datalistAction()
	{
		$response = array();
		$input = $this->getRequestInput();
		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		$applicationId = $input->get('application_id');

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($applicationId);
		$accessPolicy = $appConfig->authorizationAccessPolicy;

		if ($accessPolicy instanceof AuthorizationThreewayWithEntitiesAccessPolicy) {
			$response = $accessPolicy->getEntityTree($input);
		}

		$this->getResponse()->setResponseData($response);
	}

	public function saveAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();

		$user = $this->getUserOrGroupFromRequestKey('user_id');

		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($input->get('application_id'));

		// Check if current user is allowed to do anything with permissions for selected user
		$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		if ($appConfig instanceof ApplicationConfiguration) {
			
			$appConfig->authorizationAccessPolicy->updateAccessPolicy($user, $input);
			$this->writeAuditLog('update access policy', "Permissions updated for user '" . $user->getName()
					. "' in application '" . $appConfig->title . "'");
		}
	}

	public function savegroupAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();

		$dummyGroupId = $input->get('group_id');

		$groupName = $this->dummyGroupIdToGroupName($dummyGroupId);

		$group = $this->userProvider->findGroupByName($groupName);

		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($input->get('application_id'));

		// Check if current user is allowed to do anything with permissions for selected group
		$this->checkActionPermission($group->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		if ($appConfig instanceof ApplicationConfiguration) {
			
			$appConfig->authorizationAccessPolicy->updateAccessPolicy($group, $input);
			$this->writeAuditLog('update access policy', "Permissions updated for group '" . $group->getName()
					. "' in application '" . $appConfig->title . "'");
		}
	}

}
