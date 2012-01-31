<?php

namespace Supra\Cms\CheckPermissions;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\CmsAction;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;

class CheckPermissionsController extends CmsAction
{
	const REQUEST_KEY_CHECK_PERMISSIONS = '_check-permissions';

	const REQUEST_KEY_ID = 'id';
	const REQUEST_KEY_TYPE = 'type';

	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'index';

	public function indexAction()
	{
		$request = $this->getRequest();

		$entitiesToQuery = $request->getPostValue(self::REQUEST_KEY_CHECK_PERMISSIONS);

		$result = array();

		$ap = ObjectRepository::getAuthorizationProvider($this);

		$user = $this->getUser();

		foreach ($entitiesToQuery as $entityToQuery) {

			$id = $entityToQuery[self::REQUEST_KEY_ID];
			$alias = $entityToQuery[self::REQUEST_KEY_TYPE];
			
			$applicationNamespace = $ap->getApplicationNamespaceFromAlias($alias);
			
			$appConfig = ObjectRepository::getApplicationConfiguration($applicationNamespace);
			
			$policy = $appConfig->authorizationAccessPolicy;
			
			if($policy instanceof AuthorizationThreewayWithEntitiesAccessPolicy) {
				
				$entity = $policy->getAuthorizedEntityFromId($id);
				
				if(!empty($entity)) {
					$result[] = $policy->getPermissionStatusesForAuthorizedEntity($user, $entity);
				}
			}
		}

		$this->getResponse()
				->setResponsePermissions($result);
	}

}