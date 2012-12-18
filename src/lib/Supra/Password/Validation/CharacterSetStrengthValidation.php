<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception\PasswordPolicyException;

class CharacterSetStrengthValidation implements PasswordValidationInterface
{
	/**
	 * @var boolean
	 */
	private $requiresAlphanumeric;
	
	/**
	 * @var boolean
	 */
	private $requiresSpecialChars;
	
	
	/**
	 * @return string
	 */
	public function getFilterRequirements()
	{
		if ($this->requiresAlphanumeric && $this->requiresSpecialChars) {
			return 'Must use the characters a-z, A-Z, 0-9 and the following: !@#$%^&*?-_~/\\';
		}
		else if ($this->requiresAlphanumeric) {
			return 'Must use the characters a-z, A-Z, 0-9';
		}
		else if ($this->requiresSpecialChars) {
			return 'Must use the special characters like following: !@#$%^&*?-_~/\\';
		}
	}
	
	/**
	 * Filter configuration
	 * 
	 * @param boolean $requiresAlphanumeric
	 * @param boolean $requiresSymbols
	 */
	public function __construct($requiresAlphanumeric, $requiresSpecialChars)
	{
		$this->requiresAlphanumeric = (bool) $requiresAlphanumeric;
		$this->requiresSpecialChars = (bool) $requiresSpecialChars;
	}
	
	/**
	 * 
	 */
	public function validatePassword(AuthenticationPassword $password, User $user)
	{
		$passwordString = $password->__toString();
		
		if ($this->requiresAlphanumeric && ! preg_match("/[a-zA-Z]+[0-9]+/", $passwordString)) {
			 throw new PasswordPolicyException('Password must be mix of alpha and numeric characters');
		}
		
		if ($this->requiresSpecialChars && ! preg_match("/[\!,@,#,\$,%,\^,&,\*,\?,-,_,~,\/,\\\]/", $passwordString)) {
			 throw new PasswordPolicyException('Password must contain at least one special character');
		}
	}
}