<?php

namespace Supra\Authorization;

use Supra\User\Entity\Abstraction\User;

/**
 * Interface must be implemented if class instances are to be 
 * used as objects in ACL.
 */
interface AuthorizedEntityInterface
{
	/**
	 * @param User $user
	 * @param string $permission
	 * @param Boolean $grant
	 * @return boolean
	 */
	public function authorize(User $user, $permission, $grant);
	
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
	public function getAuthorizationAncestors();

	/**
	 * @param AuthorizedProvider $ap
	 */
	public static function registerPermissions(AuthorizationProvider $ap);
}
