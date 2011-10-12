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
use Supra\User\Entity\Group as RealGroup;
use Supra\User\Entity\User as RealUser;
use Supra\Authorization\Exception\ConfigurationException as AuthorizationConfigurationException;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;

class PermissionpropertiesAction extends InternalUserManagerAbstractAction
{

	public function datalistAction()
	{
		$response = array();
		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		$applicationId = $this->getRequest()->getQueryValue('application_id');

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($applicationId);

		if ($appConfig->authorizationAccessPolicy instanceof AuthorizationThreewayWithEntitiesAccessPolicy) {

			$response = $appConfig->authorizationAccessPolicy->getEntityTree($this->request);
		}

		$this->getResponse()->setResponseData($response);
	}

	public function saveAction()
	{
		$this->isPostRequest();
		
		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($this->getRequest()->getPostValue('application_id'));

		$user = $this->getUserOrGroupFromRequestKey('user_id');

		// Check if current user is allowed to do anything with permissions for selected user/group
		$this->checkActionPermission($user->getGroup(), RealGroup::PERMISSION_MODIFY_USER_NAME);

		if ($appConfig instanceof ApplicationConfiguration) {
			$appConfig->authorizationAccessPolicy->updateAccessPolicy($user, $this->getRequest());
		}
	}

}
