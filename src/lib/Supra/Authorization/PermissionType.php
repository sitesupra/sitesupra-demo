<?php

namespace Supra\Authorization;

class PermissionType 
{
	/**
	 * @var string
	 */
	private $name;
	
	/**
	 * @var integer
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

