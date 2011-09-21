<?php

namespace Project\AutoregisterAuthenticationAdapter;

use Supra\User\Authentication\Adapters\HashAdapter;
use Supra\User\Entity\User;

/**
 * Development authentication adapter automatically registering new users
 */
class AutoregisterAuthenticationAdapter extends HashAdapter
{
	public function findUser($login, $password)
	{
		$user = new User();
		$user->setName($login);
		$user->setEmail($login);
		
		$this->credentialChange($user, $password);
		
		return $user;
	}
}
