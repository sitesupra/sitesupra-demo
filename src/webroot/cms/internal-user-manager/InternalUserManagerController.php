<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\Authorization\AuthorizedAction;
use Supra\Authorization\AuthorizedControlerExecuteAction;
use Supra\User\Entity\Abstraction\User;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 */
class InternalUserManagerController extends DistributedController implements AuthorizedControllerInterface
{
	 /** Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'root';
	
	public function getPermissionTypes() 
	{
		return array(
				new \Supra\Authorization\ControllerAllAccessPermission(),
				new \Supra\Authorization\ControllerSomeAccessPermission(),
				);
	}
	
	public function getAuthorizationId() 
	{
		return 42;
	}
	
	public function getAuthorizationClass() 
	{
		return __CLASS__;
	}
	
	public function authorize(User $user, $permissionType)
	{
		return true;
	}
}
	
