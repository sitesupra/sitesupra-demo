<?php

namespace Supra\Tests\Authorization;

use Supra\NestedSet\Node\ArrayNode;

use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\PermissionType;

use Supra\User\Entity\Abstraction\User;

class DummyAuthorizedEntity extends ArrayNode implements AuthorizedEntityInterface
{
	const PERMISSION_EDIT = 8;
	const PERMISSION_DELETE = 16;
	
	public function __construct($title) 
	{
		$this->setTitle($title);
	}
	
	function getAuthorizationId() 
	{
		return $this->getTitle();
	}
	
	function getAuthorizationClass() 
	{
		return __CLASS__;
	}
	
	function getPermissionTypes() 
	{
		return array(
			$this->getEditPermissionType(),
			$this->getDeletePermissionType()	
		);
	}
	
	function getEditPermissionType() 
	{
		return new PermissionType('edit', self::PERMISSION_EDIT);
	}
	
	function getDeletePermissionType() 
	{
		return new PermissionType('delete', self::PERMISSION_DELETE);
	}
	
	function authorize(User $user, $permissionType) 
	{
		return true;
	}
	
	function getAuthorizationAncestors($includeSelf = true) 
	{
		return $this->getAncestors(0, $includeSelf);
	}
}
