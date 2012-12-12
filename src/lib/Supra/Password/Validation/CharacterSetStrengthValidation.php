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
	private $requiresAlpha;
	
	/**
	 * @var boolean
	 */
	private $requiresNumeric;
	
	/**
	 * @var boolean
	 */
	private $requiresSymbols;
	
	/**
	 * Filter configuration
	 * 
	 * @param boolean $requiresAlpha
	 * @param boolean $requiresNumeric
	 * @param boolean $requiresSymbols
	 */
	public function __construct($requiresAlpha, $requiresNumeric, $requiresSymbols)
	{
		$this->requiresAlpha = (bool) $requiresAlpha;
		$this->requiresNumeric = (bool) $requiresNumeric;
		$this->requiresSymbols = (bool) $requiresSymbols;
	}
	
	/**
	 * 
	 */
	public function validatePassword(AuthenticationPassword $password, User $user)
	{
		$passwordString = $password->__toString();
		
		if ($this->requiresNumeric && ! preg_match("/[0-9]/", $passwordString)) {
			 throw new PasswordPolicyException('Password must contain at least one digit');
		}
		
		if ($this->requiresAlpha && ! preg_match("/[A-Za-z]/", $passwordString)) {
			 throw new PasswordPolicyException('Password must contain at least one alphanumeric character');
		}
		
		if ($this->requiresSymbols && ! preg_match("/[\!,@,#,\$,%,\^,&,\*,\?,-,_,~,\/,\\\]/", $passwordString)) {
			 throw new PasswordPolicyException('Password must contain at least one symbol');
		}
	}
}