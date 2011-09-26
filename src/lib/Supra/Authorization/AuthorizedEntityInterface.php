<?php

namespace Supra\Authorization;

use Supra\User\Entity\Abstraction\User;

interface AuthorizedEntityInterface
{
	/**
	 * @return array
	 */
	public function getPermissionTypes();

	/**
	 * @param User $user
	 * @param string $permissionType
	 * @return boolean
	 */
	public function authorize(User $user, $permissionType);
	
	/**
	 * @return string
	 */
	public function getAuthorizationId();
	
	/**
	 * @return string;
	 */
	public function getAuthorizationClass();
	
	/**
	 * @param boolean $includeSelf
	 * @return array
	 */
	public function getAuthorizationAncestors($includeSelf = true);
	
}
