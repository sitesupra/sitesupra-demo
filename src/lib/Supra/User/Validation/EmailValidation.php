<?php

namespace Supra\User\Validation;

use Supra\User\Exception;
use Supra\User\Entity\User;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;

/**
 * User Email validation
 */
class EmailValidation implements UserValidationInterface
{
	/**
	 * {@inheritdoc}
	 * @param User $user
	 */
	public function validateUser(User $user)
	{
		$em = ObjectRepository::getEntityManager($this);
		
		if (empty($em)) {
			throw new Exception\LogicException("Entity manager not passed to email uniqueness validation");
		}
		
		$email = $user->getEmail();
		$result = filter_var($email, FILTER_VALIDATE_EMAIL);
		
		if(empty($result)) {
			throw new Exception\RuntimeException('Email isn\'t valid');
		}
		
		$repo = $em->getRepository(User::CN());
		/*@var $detectedUser User */
		
		$detectedUser = $repo->findOneByEmail($email);
		if( ! empty($detectedUser) && ($detectedUser->getId() != $user->getId())) {
			throw new Exception\RuntimeException('User with such email already exists');
		}
	}

}
