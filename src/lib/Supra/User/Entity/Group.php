<?php

namespace Supra\User\Entity;

use Supra\ObjectRepository\ObjectRepository;

use Supra\User\Entity\User as RealUser;

/**
 * Group object
 * @Entity
 * @Table(name="group") 
 */
class Group extends Abstraction\User
{
	public function getUsers() 
	{
		$em = ObjectRepository::getEntityManager(get_called_class());
		
		$userRepository = $em->getRepository(RealUser::CN());
		
		$users = $userRepository->findBy(array('group' => $this));
		
		return $users;
	}
}
