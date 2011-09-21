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
	 * @return string
	 */
	public function getAuthorizationClass();
	
	/**
	 * @return array
	 */
	public function getPermissionTypes();

	/**
	 * @return boolean
	 */
	public function authorize(User $user, $permissionType);
	
}
