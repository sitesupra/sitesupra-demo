<?php

namespace Supra\Authorization\Permission;

use Supra\Authorization\AuthorizationProvider;
use Supra\Authorization\Exception;
use Supra\User\Entity\AbstractUser;
/**
 * Base class for permission type.
 */
class Permission 
{
	const ALLOW_MASK = 0x800000; // do not change this, bro.
	
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
	 * Creates permission type definition. Checks whether the $class 
	 * actually implements any of supported authorization identities.
	 * @param String $name
	 * @param String $mask
	 * @param String $class 
	 */
	function __construct($name, $mask, $class) 
	{
		$this->name = $name;
		$this->mask = $mask;
		
		$className = $class; 
		
		$ref = new \ReflectionClass($class);
		if (
			! ( 
					$class == AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS ||
					$ref->isSubclassOf(AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS) ||
					$ref->implementsInterface(AuthorizationProvider::AUTHORIZED_CONTROLLER_INTERFACE) ||
					$ref->implementsInterface(AuthorizationProvider::AUTHORIZED_ENTITY_INTERFACE)
				)
		) {
			throw new Exception\ConfigurationException('Can not register permission type for class that does not implement ' . 
					AuthorizationProvider::AUTHORIZED_CONTROLLER_INTERFACE . ' or ' . 
					AuthorizationProvider::AUTHORIZED_ENTITY_INTERFACE . ' or is not class/sublclass of ' .
					AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS
				);
		}
		
		if($ref->isSubclassOf(AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS)) {
			$className = AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS;
		}
		else if ($ref->implementsInterface(AuthorizationProvider::AUTHORIZED_ENTITY_INTERFACE)) {
			$className = $class::getAuthorizationClass();
		}
		
		$this->class = $className;
	}
	
	/**
	 * Returns name of permission type.
	 * @return string
	 */
	public function getName() 
	{
		return $this->name;
	}
	
	/**
	 * Returns mask for this permission type. You probably 
	 * should not use this, see getAllowMask() and getDenyMask() instead.
	 * @return integer
	 */
	public function getMask() 
	{
		return $this->mask;
	}
	
	/**
	 * Returns "ALLOW" status mask for permission type.
	 * @return integer
	 */
	public function getAllowMask() 
	{
		//\Log::debug('ALLOW MASK: ', sprintf('%X | %X => %X', $this->mask, self::ALLOW_MASK, $this->mask | self::ALLOW_MASK));
		return $this->mask | self::ALLOW_MASK;
	}
	
	/**
	 * Returns "DENY" status mask for permission type.
	 * @return integer
	 */
	public function getDenyMask() 
	{
		return $this->mask;
	}
	
	/**
	 * Returns class name for instances of which this permission type can be used.
	 * @return string
	 */
	public function getClass() 
	{
		return $this->class;
	}
	
	/**
	 * This is being called by authorization provider when permission of this type 
	 * has been granted (set to ALLOW).
	 * @param AbstractUser $user
	 * @param mixed $object
	 */
	public function granted(AbstractUser $user, $object) 
	{
		
	}
	
	/**
	 * This is being called by authorization provider when permission of this type 
	 * has been revoked (set to DENY).
	 * @param AbstractUser $user
	 * @param mixed $object 
	 */
	public function revoked(AbstractUser $user, $object)
	{
		
	}
	
	/**
	 * This is being called by authorization provider when permission of this type 
	 * has been removed from acl (set to NONE).
	 * @param AbstractUser $user
	 * @param mixed $object 
	 */
	public function removed(AbstractUser $user, $object)
	{
		
	}	
}

