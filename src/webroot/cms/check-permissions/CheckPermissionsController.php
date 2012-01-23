<?php

namespace Supra\Cms\CheckPermissions;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\CmsAction;

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
			$classAlias = $entityToQuery[self::REQUEST_KEY_TYPE];

			$objectIdentity = $ap->createObjectIdentityWithClassAlias($id, $classAlias);

			$result[] = $ap->getPermissionStatusesForAuthorizedEntity($user, $objectIdentity);
		}

		$this->getResponse()
				->setResponsePermissions($result);
	}

}