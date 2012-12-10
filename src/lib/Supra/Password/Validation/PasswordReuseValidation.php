<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception;
use Supra\Password\Entity\PasswordHistoryRecord;
use Supra\Password\Exception\PasswordPolicyException;

/**
 * 
 */
class PasswordReuseValidation implements PasswordValidationInterface
{
	/**
	 * @var integer
	 */
	private $passwordReUseLimit;
	
	
	/**
	 * Filter configuration
	 */
	public function __construct($passwordReUseLimit)
	{
		if ( (int) $passwordReUseLimit < 1) {
			throw new Exception\RuntimeException('Re-use limit value should be positive integer');
		}
		
		$this->passwordReUseLimit = (int) $passwordReUseLimit;
	}
	
	/**
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 * @throws Exception\PasswordPolicyException
	 */
	public function validatePassword(AuthenticationPassword $password, User $user)
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		
		$qb = $em->createQueryBuilder();
		$qb->from(PasswordHistoryRecord::CN(), 'ph')
				->select('ph')
				->where('ph.userId = :userId')
				->orderBy('ph.id')
				->setMaxResults($this->passwordReUseLimit);
		
		$passwords = $qb->getQuery()
				->setParameter('userId', $user->getId())
				->getResult();
				
		foreach ($passwords as $oldPassword) {
			
			/* @var $oldPassword PasswordHistoryRecord */
			if ($oldPassword->isEquals($password)) {
				throw new PasswordPolicyException("Password policy restricts to use last {$this->passwordReUseLimit} already used password(s), please select another one");
			}
		}
	}
	
}