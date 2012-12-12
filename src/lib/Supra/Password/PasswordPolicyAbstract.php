<?php

namespace Supra\Password;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception\PasswordPolicyException;
use \DateTime;

abstract class PasswordPolicyAbstract implements PasswordPolicyInterface
{
		
	/**
	 * Array of password validation filters
	 * 
	 * @var array
	 */
	protected $validationFilters = array();
	
	/**
	 * Iso-format period string
	 * 
	 * @var string
	 */
	protected $expirationPeriod;
	
	
	/**
	 * Adds validation filter to array
	 * 
	 * @param Validation\UserValidationInterface $validationFilter 
	 */
	public function addValidationFilter(Validation\PasswordValidationInterface $validationFilter)
	{		
		$cn = get_class($validationFilter);
		
		if (array_key_exists($cn, $this->validationFilters)) {
			throw new Exception\RuntimeException("Filter {$cn} already exists in validation filter array");
		}
		
		ObjectRepository::setCallerParent($validationFilter, $this);
		$this->validationFilters[$cn] = $validationFilter;
	}

	/**
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 */
	public function validate(AuthenticationPassword $password, User $user)
	{
		if ($password->isEmpty()) {
			throw new PasswordPolicyException('Empty passwords are not allowed');
		}
		
		foreach ($this->validationFilters as $filter) {
			/* @var $filter Validation\PasswordValidationInterface */
			$filter->validatePassword($password, $user);
		}
	}
	
	/**
	 * Sets password expiration period
	 * 
	 * @param string $period
	 */
	public function setPasswordExpirationPeriod($period)
	{
		$this->expirationPeriod = $period;
	}
	
	/**
	 * Validates password expiration
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 */
	public function validateUserPasswordExpiration(User $user)
	{
		if ($this->expirationPeriod) {

			$passwordChangeTime = $user->getLastPasswordChangeTime();
			
			if ($passwordChangeTime instanceof DateTime) {
			
				$now = new DateTime('now');
				$interval = new \DateInterval($this->expirationPeriod);
				$expiryDate = $passwordChangeTime->add($interval);
				
				if ($expiryDate > $now) {
					return true;
				}
			}
			
			throw new Exception\PasswordExpiredException('Your password is expired');
		}
		
	}
}
