<?php

namespace Supra\Authorization;

use Supra\User\Entity\AbstractUser;

/**
 * Interface must be implemented if class instances are to be 
 * used as objects in ACL.
 */
interface AuthorizedEntityInterface
{
	/**
	 * @param AbstractUser $user
	 * @param string $permission
	 * @param Boolean $grant
	 * @return boolean
	 */
	public function authorize(AbstractUser $user, $permission, $grant);
	
	/**
	 * @return string
	 */
	public function getAuthorizationId();
	
	/**
	 * @return string;
	 */
	public static function getAuthorizationClass();
	
	/**
	 * @param boolean $includeSelf
	 * @return array
	 */
	public function getAuthorizationAncestors();

	/**
	 * @param AuthorizedProvider $ap
	 */
	public static function registerPermissions(AuthorizationProvider $ap);
	
	/**
	 * @return string
	 */
	public static function getAlias();
}
