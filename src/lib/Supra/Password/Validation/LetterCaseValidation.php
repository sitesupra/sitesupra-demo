<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception;

/**
 * Password lower/upper case letter validation
 */
class LetterCaseValidation implements PasswordValidationInterface
{
	/**
	 * @var boolean
	 */
	private $requiresUpper;
	
	/**
	 * @var boolean
	 */
	private $requiresLower;
	

	/**
	 * @return string
	 */
	public function getFilterRequirements()
	{
		if ($this->requiresLower && $this->requiresUpper) {
			return "Must contain a combination of upper and lower case letters.";
		} 
		else if ($this->requiresLower) {
			return "Must contain at least one lower case letter.";
		}
		else if ($this->requiresUpper) {
			return "Must contain at least one upper case letter.";
		}
	}
	
	/**
	 * Filter configuration
	 */
	public function __construct($requiresUpper = false, $requiresLower = false)
	{
		if ($requiresLower === false && $requiresUpper === false) {
			throw new Exception\RuntimeException('Letter case filter should have at least one of the argument to be defined');
		}
		
		$this->requiresUpper = $requiresUpper;
		$this->requiresLower = $requiresLower;
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
		
		if ($this->requiresLower && mb_strtoupper($passwordString) == $passwordString) {
			throw new Exception\PasswordPolicyException('Password must contain at least one lowercase letter');
		}
		
		if ($this->requiresUpper && mb_strtolower($passwordString) == $passwordString) {
			throw new Exception\PasswordPolicyException('Password must contain at least one uppercase letter');
		}
	}
}



