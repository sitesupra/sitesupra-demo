<?php

namespace Supra\Authorization\Exception;

class AccessDeniedException extends RuntimeException
{
	private $user;
	private $object;
	private $permissionName;
	
	public function __construct($user, $object, $permissionName)
	{
		$this->user = $user;
		$this->object = $object;
		$this->permissionName = $permissionName;
	}
	
	public function getUser()
	{
		return $this->user;
	}
	
	public function getObject()
	{
		return $this->object;
	}
	
	public function getPermissionName()
	{
		return $this->permissionName;
	}
}

