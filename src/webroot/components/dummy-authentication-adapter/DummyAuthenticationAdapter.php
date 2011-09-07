<?php

namespace Project\AuthenticationAdapter;

use Supra\User\Authentication\Adapters\HashAdapter;
use Supra\User\Entity\User;

/**
 * Development authentication adapter automatically registering new users
 */
class DummyAuthenticationAdapter extends HashAdapter
{
	public function findUser($login, $password)
	{
		$user = new User();
		$salt = $user->resetSalt();
		$user->setEmail($login);
		$user->setName($login);
		$passwordHash = $this->generatePasswordHash($password, $salt);
		$user->setPassword($passwordHash);
		
		return $user;
	}
}
