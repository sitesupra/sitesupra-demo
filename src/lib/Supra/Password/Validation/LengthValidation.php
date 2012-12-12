<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception;

/**
 * Password min/max length validation
 */
class LengthValidation implements PasswordValidationInterface
{
	/**
	 * @var integer
	 */
	private $minLength;
	
	/**
	 * @var integer
	 */
	private $maxLength;
	
	
	/**
	 * Filter configuration
	 * 
	 */
	public function __construct($minLength = null, $maxLength = null)
	{
		if (is_null($minLength) && is_null($maxLength)) {
			throw new Exception\RuntimeException('Length filter requires at least one of the arguments to be specified');
		}
		
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}
	
	/**
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 * @throws Exception\PasswordPolicyException
	 */
	public function validatePassword(AuthenticationPassword $password, User $user)
	{
		$passwordString = $password->__toString();
		$length = mb_strlen($passwordString);
		
		if ( ! is_null($this->minLength) && $length < $this->minLength) {
			throw new Exception\PasswordPolicyException("Password length should be at least {$this->minLength} characters");
		}
		
		if ( ! is_null($this->maxLength) && $length > $this->maxLength) {
			throw new Exception\PasswordPolicyException("Password length should be not more than {$this->maxLength} characters");
		}
	}
}
