<?php

namespace Supra\Authentication\Adapter\Algorithm;

use Supra\Authentication\AuthenticationPassword;

/**
 * Plain password algorithm
 */
class PlainAlgorithm implements CryptAlgorithm
{
	/**
	 * @param AuthenticationPassword $password
	 * @param string $salt
	 */
	public function crypt(AuthenticationPassword $password, $salt = null)
	{
		return $password;
	}

	/**
	 * @param AuthenticationPassword $password
	 * @param string $hash
	 * @param string $salt
	 * @return boolean
	 */
	public function validate(AuthenticationPassword $password, $hash, $salt = null)
	{
		return ($password === $hash);
	}
}
