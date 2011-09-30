<?php

namespace Supra\Authorization\Permission;

use Supra\Authorization\AuthorizationProvider;
use Supra\User\Entity\Abstraction\User;
use Supra\Authorization\Exception\AuthorizationConfigurationException;

/**
 * Base class for permission.
 */
class Permission 
{
	/**
	 * @var String
	 */
	private $name;
	
	/**
	 * @var Integer
	 */
	private $mask;
	
	/**
	 *
	 * @var String
	 */
	private $class;
	
	/**
	 *
	 * @param String $name
	 * @param String $mask
	 * @param String $class 
	 */
	function __construct($name, $mask, $class) 
	{
		$this->name = $name;
		$this->mask = $mask;
		
		$ref = new \ReflectionClass($class);
		if (
			! ( 
					$class == AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS ||
					$ref->isSubclassOf(AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS) ||
					$ref->implementsInterface(AuthorizationProvider::AUTHORIZED_CONTROLLER_INTERFACE) ||
					$ref->implementsInterface(AuthorizationProvider::AUTHORIZED_ENTITY_INTERFACE)
				)
		) {
			throw new AuthorizationConfigurationException('Can not register permission type for class that does not implement ' . 
					self::AUTHORIZED_CONTROLLER_INTERFACE . ' or ' . 
					self::AUTHORIZED_ENTITY_INTERFACE . ' or is not class/sublclass of ' .
					self::APPLICATION_CONFIGURATION_CLASS
				);
		}
		
		if($ref->isSubclassOf(AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS)) {
			$class = AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS;
		}
		else if ($ref->implementsInterface(AuthorizationProvider::AUTHORIZED_ENTITY_INTERFACE)) {
			$class = $class::getAuthorizationClass();
		}
		
		$this->class = $class;
	}
	
	public function getName() 
	{
		return $this->name;
	}
	
	public function getMask() 
	{
		return $this->mask;
	}
	
	public function getClass() 
	{
		return $this->class;
	}
	
	/**
	 * This is being called by authorization provider when permission of this type 
	 * has been granted.
	 * @param User $user
	 * @param mixed $object
	 */
	public function granted(User $user, $object) 
	{
		
	}
	
	/**
	 * This is being called by authorization provider when permission of this type 
	 * has been revoked.
	 * @param User $user
	 * @param mixed $object 
	 */
	public function revoked(User $user, $object)
	{
		
	}
}

