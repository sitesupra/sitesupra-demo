<?php

namespace Supra\Authentication\Adapter\Algorithm;

use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception;

/**
 * SHA1 algorithm
 */
class Sha1Algorithm implements CryptAlgorithm
{
	/**
	 * @param AuthenticationPassword $password
	 * @param string $salt
	 */
	public function crypt(AuthenticationPassword $password, $salt = null)
	{
		if (empty($salt)) {
			throw new Exception\RuntimeException("User password salt is not permitted to be empty");
		}
		
		$hash = hash_hmac('sha1', (string) $password, $salt, false);
		
		return $hash;
	}
	
	/**
	 * @param AuthenticationPassword $password
	 * @param string $hash
	 * @param string $salt
	 * @return boolean
	 */
	public function validate(AuthenticationPassword $password, $hash, $salt = null)
	{
		$expectedHash = $this->crypt($password, $salt);
		$valid = ($expectedHash === $hash);
		
		return $valid;
	}
}
