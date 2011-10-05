<?php

namespace Supra\Authentication;

/**
 * Password incubator object, secures password from showing up in traces
 */
class AuthenticationPassword
{
	/**
	 * Plain password
	 * @var string
	 */
	private $password;

	/**
	 * @param string $plainPassword
	 */
	public function __construct(&$plainPassword)
	{
		$this->password = (string) $plainPassword;
		
		// Unset the password from the variable
		$plainPassword = null;
	}
	
	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		$empty = ($this->password === '');
		
		return $empty;
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->password;
	}
}
