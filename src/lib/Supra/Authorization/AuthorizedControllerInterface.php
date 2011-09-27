<?php

namespace Supra\Authorization;

use Supra\User\Entity\Abstraction\User;

interface AuthorizedControllerInterface
{
	/**
	 * @return string
	 */
	public function getAuthorizationId();

	/**
	 * @return boolean
	 */
	public function authorize(User $user, $permission);
}
