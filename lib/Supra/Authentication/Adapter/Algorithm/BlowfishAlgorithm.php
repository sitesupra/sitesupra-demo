<?php

namespace Supra\Authentication\Adapter\Algorithm;

use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception;

/**
 * Uses blowfish hashing algorythm
 */
class BlowfishAlgorithm implements CryptAlgorithm
{
	/**
	 * Hashing method used
	 * @var string
	 */
	protected static $algorithm = '2a';
	
	/**
	 * Chosen algorithm strength
	 * @var int
	 */
	protected $strength = 12;
	
	/**
	 * @var int
	 */
	protected static $saltLength = 22;

	/**
	 * @var string
	 */
	protected static $saltPool = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	
	/**
	 * @var int
	 */
	protected static $expectedLength = 60;
	
	/**
	 * @param AuthenticationPassword $password
	 * @param string $salt
	 */
	public function crypt(AuthenticationPassword $password, $salt = null)
	{
		// Not using user provided salt, generating
		$saltBase = $this->generateSalt();
		$strength = str_pad((string) $this->strength, 2, '0', STR_PAD_LEFT);
		$salt = '$' . self::$algorithm . '$' . $strength . '$' . $saltBase . '$';
		
		$hash = crypt((string) $password, $salt);
		
		if (strlen($hash) != self::$expectedLength) {
			throw new Exception\RuntimeException("Generated hash doesn't match expected size");
		}
		
		return $hash;
	}

	/**
	 * @param int $strength
	 * @throws Exception\RuntimeException
	 * @throws Exception\RuntimeException
	 */
	public function setStrength($strength)
	{
		if ( ! is_int($strength)) {
			throw new \InvalidArgumentException("Hashing strength must be integer");
		}
		
		if ($strength < 4 || $strength > 31) {
			throw new \RangeException("Blowfish hashing algorithm strength must be in range 04-31");
		}
		
		$this->strength = $strength;
	}

	/**
	 * @param AuthenticationPassword $password
	 * @param string $hash
	 * @param string $salt
	 * @return boolean
	 */
	public function validate(AuthenticationPassword $password, $hash, $salt = null)
	{
		$expectedHash = crypt((string) $password, $hash);
		$valid = ($expectedHash === $hash);
		
		return $valid;
	}
	
	/**
	 * @param string $characters
	 * @param int $length
	 * @return string
	 */
	protected function generateSalt()
	{
		$pool = self::$saltPool;
		$length = self::$saltLength;
		
		if ( ! is_string($pool) || $pool == '') {
			throw new Exception\RuntimeException("Salt allowed character list cannot be empty");
		}
		
		$poolLength = strlen($pool);
		
		if ( ! is_int($length) || $length <= 0) {
			throw new Exception\RuntimeException("Salt length must be greater than zero");
		}
		
		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= substr($pool, mt_rand(0, $poolLength - 1), 1);
		}
		
		if (strlen($salt) != $length) {
			throw new Exception\RuntimeException("Expected salt length doesn't match actual length");
		}
		
		return $salt;
	}
}
