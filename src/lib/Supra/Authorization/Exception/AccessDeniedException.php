<?php

namespace Supra\Authorization\Exception;

class AccessDeniedException extends \RuntimeException 
{
	private $user;
	private $object;
	private $permissionTypeName;
	
	public function __construct($user, $object, $permissionTypeName) 
	{
		$this->user = $user;
		$this->object = $object;
		$this->permissionTypeName = $permissionTypeName;
	}
	
	public function getUser() 
	{
		return $this->user;
	}
	
	public function getObject() 
	{
		return $this->object;
	}
	
	public function getPermissionTypeName() 
	{
		return $this->permissionTypeName;
	}
}

