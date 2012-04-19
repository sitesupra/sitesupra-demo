<?php

namespace Supra\Authorization;

use Supra\User\Entity\AbstractUser;

interface AuthorizedControllerInterface
{
	/**
	 * @return string
	 */
	public function getAuthorizationId();

	/**
	 * @return boolean
	 */
	public function authorize(AbstractUser $user, $permission);
}
