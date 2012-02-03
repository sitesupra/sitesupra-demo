<?php

namespace Supra\Tests\User\Mockup;

/**
 * Description of MockupUserProvider
 */
class MockupUserProvider extends \Supra\User\UserProvider
{
	public function createUser()
	{
		return new \Supra\User\Entity\User();
	}
	
	public function updateUser(\Supra\User\Entity\User $user)
	{
		// does nothing
	}
}
