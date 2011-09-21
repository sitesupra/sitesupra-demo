<?php

namespace Supra\Authorization;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class PermissionType 
{
	/**
	 * @var String;
	 */
	private $name;
	
	/**
	 * @var Integer
	 */
	private $mask;
	
	function __construct($name, $mask) 
	{
		$this->name = $name;
		$this->mask = $mask;
	}
	
	function getName() 
	{
		return $this->name;
	}
	
	function getMask() 
	{
		return $this->mask;
	}
	
	function grant() 
	{
		
	}
	
	function revoke() 
	{
		
	}
}

