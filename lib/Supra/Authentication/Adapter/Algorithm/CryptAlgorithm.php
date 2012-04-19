<?php

namespace Supra\Authentication\Adapter\Algorithm;

use Supra\Authentication\AuthenticationPassword;

/**
 * 
 */
interface CryptAlgorithm
{
	const CN = __CLASS__;
	
	public function crypt(AuthenticationPassword $password, $salt = null);
	
	public function validate(AuthenticationPassword $password, $hash, $salt = null);
}
