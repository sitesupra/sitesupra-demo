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
class PasswordHistoryValidation implements PasswordValidationInterface
{
	/**
	 * @var integer
	 */
	private $recordsLimit;
	
	/**
	 * @return string
	 */
	public function getFilterRequirements()
	{
		return "Must not be one of your previous {$this->recordsLimit} passwords";
	}
	
	/**
	 * Filter configuration
	 */
	public function __construct($recordsLimit)
	{
		if ( (int) $recordsLimit < 1) {
			throw new Exception\RuntimeException('Password history records limit value should be positive integer');
		}
		
		$this->recordsLimit = (int) $recordsLimit;
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
				->orderBy('ph.id', 'DESC')
				->setMaxResults($this->recordsLimit);
		
		$passwords = $qb->getQuery()
				->setParameter('userId', $user->getId())
				->getResult();
				
		foreach ($passwords as $oldPassword) {
			
			/* @var $oldPassword PasswordHistoryRecord */
			if ($oldPassword->isEquals($password)) {
				throw new PasswordPolicyException("Password policy restricts to use last {$this->recordsLimit} already used password(s), please select another one");
			}
		}
	}
	
}