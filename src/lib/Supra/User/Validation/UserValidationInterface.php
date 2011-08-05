<?php

namespace Supra\User\Validation;

interface UserValidationInterface
{
	public function validateUser(\Supra\User\Entity\User $user);
}

