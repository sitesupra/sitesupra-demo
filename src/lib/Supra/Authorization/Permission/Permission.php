<?php

namespace Supra\Authorization\Permission;

class Permission 
{
	/**
	 * @var String
	 */
	private $name;
	
	/**
	 * @var Integer
	 */
	private $mask;
	
	/**
	 *
	 * @var String
	 */
	private $class;
	
	function __construct($name, $mask, $class) 
	{
		$this->name = $name;
		$this->mask = $mask;
		$this->class = $class;
	}
	
	public function getName() 
	{
		return $this->name;
	}
	
	public function getMask() 
	{
		return $this->mask;
	}
	
	public function getClass() 
	{
		return $this->class;
	}

	
	public function grant() 
	{
		
	}
	
	public function revoke() 
	{
		
	}
}

