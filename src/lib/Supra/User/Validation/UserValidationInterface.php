<?php

namespace Supra\User\Validation;

use Supra\User\Entity\User;
use Supra\User\Exception;

interface UserValidationInterface
{
	/**
	 * Validates the user
	 * @param User $user
	 * @throws Exception\RuntimeException in case of invalid user
	 */
	public function validateUser(User $user);
}
