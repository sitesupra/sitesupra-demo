<?php


namespace Supra\User\Validation;

use Supra\User;
use Supra\User\Exception;
use Supra\ObjectRepository\ObjectRepository;
/**
 * User Email validation
 */
class EmailValidation implements UserValidationInterface
{
	
	public function validateUser(\Supra\User\Entity\User $user)
	{
		$email = $user->getEmail();
		$result = filter_var($email, FILTER_VALIDATE_EMAIL);
		
		if(empty($result)) {
			throw new Exception\RuntimeException('Email isn\'t valid');
		}
		
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository('Supra\User\Entity\User');
		/*@var $detectedUser \Supra\User\Entity\User */
		
		$detectedUser = $repo->findOneByEmail($email);
		if( ! empty($detectedUser) && ($detectedUser->getId() != $user->getId())) {
			throw new Exception\RuntimeException('User with such email already exists');
		}
	}

}
