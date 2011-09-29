<?php

namespace Supra\Authorization;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Supra\User\Entity\Abstraction\User;
use Supra\ObjectRepository\ObjectRepository;

use Supra\Authorization\Permission\PermissionStatus;

use Supra\Authorization\Permission\Application\ApplicationAllAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationSomeAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationExecuteAccessPermission;

use Supra\Authorization\Permission\Controller\ControllerExecutePermission;

use Supra\NestedSet\Node\NodeInterface;

use Supra\Cms\ApplicationConfiguration;

use Supra\Log\Log;

class AuthorizationProvider 
{
	const AUTHORIZED_CONTROLLER_INTERFACE = 'Supra\\Authorization\\AuthorizedControllerInterface'; // you probably should not change this.
	const AUTHORIZED_ENTITY_INTERFACE = 'Supra\\Authorization\\AuthorizedEntityInterface'; // you probably should not change this.
	const APPLICATION_CONFIGURATION_CLASS = 'Supra\\Cms\\ApplicationConfiguration'; // you probably should not change this.
	
	/**
	 * @var MutableAclProvider;
	 */
	protected $aclProvider;
	
	/**
	 * @var array
	 */
	private $permissionsByName = array();
	
	/**
	 *
	 * @var array
	 */
	private $permissionsByClass = array();
	
	/**
	 * @var Log
	 */
	private $log;
	
	
	/**
	 * Constructs AuthorizationProvider.
	 * @param EntityManager $entityManager
	 * @param array $options 
	 */
	function __construct(EntityManager $entityManager = null, $options = array()) 
	{
		if (empty($entityManager)) {
			$entityManager = ObjectRepository::getEntityManager($this);
		}
		
		$permissionGrantingStrategy = new PermissionGrantingStrategy();
		
		$this->aclProvider= new MutableAclProvider($entityManager->getConnection(), $permissionGrantingStrategy, $options);		
		
		$this->log = ObjectRepository::getLogger($this);

		$this->registerPermission(new ApplicationExecuteAccessPermission());
		$this->registerPermission(new ApplicationAllAccessPermission());
		$this->registerPermission(new ApplicationSomeAccessPermission());

		$this->registerPermission(new ControllerExecutePermission());
	}
	
	/* TODO: Check for overlapping masks in same class/sublclass tree */
	/**
	 * Registers permission type and does some validity checks.
	 * @param Permission $permission 
	 */
	private function registerPermission($permission)
	{
		$name = $permission->getName();
		$class = $permission->getClass();
		
		$ref = new \ReflectionClass($class);
		if (
			! ( 
					$class == self::APPLICATION_CONFIGURATION_CLASS ||
					$ref->isSubclassOf(self::APPLICATION_CONFIGURATION_CLASS) ||
					$ref->implementsInterface(self::AUTHORIZED_CONTROLLER_INTERFACE) ||
					$ref->implementsInterface(self::AUTHORIZED_ENTITY_INTERFACE)
				)
		) {
			throw new \RuntimeException('Can not register permission type for class that does not implement ' . 
					self::AUTHORIZED_CONTROLLER_INTERFACE . ' or ' . 
					self::AUTHORIZED_ENTITY_INTERFACE . ' or is not class/sublclass of ' .
					self::APPLICATION_CONFIGURATION_CLASS
				);
		}
		
		if ( empty($this->permissionsByName[$name])) {
			
			$this->permissionsByName[$name] = $permission;
			
			if ( empty($this->permissionsByClass[$class])) {
				$this->permissionsByClass[$class] = array();
			}
			
			$this->permissionsByClass[$class][$name] = $permission;
		}
		else {
			throw new \RuntimeException('Permission type named "' . $name . '" is already registered');
		}
	}
	
	/**
	 * Registers generic authorized entity permission type.
	 * @param string $name
	 * @param integer $mask
	 * @param string $class 
	 */
	public function registerGenericEntityPermission($name, $mask, $class) 
	{
		$permission = new Permission\Entity\EntitiyAccessPermission($name, $mask, $class);
		$this->registerPermission($permission);
	}
	
	/**
	 * Returns permission type object. Optionally checks whether the object class has permission type defined.
	 * @param string $permissionName
	 * @param mixed $objectToCheck
	 * @return Permission
	 */
	private function getPermission($permissionName, $objectToCheck = null) 
	{
		if ( ! isset($this->permissionsByName[$permissionName])) {
			throw new \RuntimeException('Permission type named "' . $permissionName . '" is not registered');
		}
		else {
			
			if ( ! empty($objectToCheck)) {
				
				$permissionsForObject = $this->getPermissionsForObject($objectToCheck);
				
				if ( !isset($permissionsForObject[$permissionName])) {
					
					throw new \RuntimeException('Class/superclass tree for ' . get_class($objectToCheck) . 
							' does not have a permission named "' . $permissionName . '"'
					);
				}
			}
			
			return $this->permissionsByName[$permissionName];
		}
	}
	
	/**
	 * Returns array of permission type names reigistered for object.
	 * @param Object $object
	 * @return array of Permission
	 */
	private function getPermissionsForObject($object) 
	{
		return $this->getPermissionsForClass(get_class($object));
	}
	
	/**
	 * Returns array of permission types registered for class name
	 * @param string $className class name
	 * @return array of Permission
	 */
	private function getPermissionsForClass($className) 
	{
		$ref = new \ReflectionClass($className);
		
		$result = array();
		
		if ( ! empty($this->permissionsByClass[$className])) {
			$result = $this->permissionsByClass[$className];
		}
		
		foreach ($this->permissionsByClass as $permissionClass => $permissions) {
			
			if ($ref->isSubclassOf($permissionClass)) {
				$result += $permissions;
			}
		}
		
		return $result;
	}
	
	/**
	 * Constructs and returns ObjectIdentity object for given object. Throws exception if object does not implement AuthorizedEntityInterface of AuthorizedController interface.
	 * @param $object
	 * @return ObjectIdentity
	 */
	private function getObjectIdentity($object)
	{
		$objectIdentity = null;
		
		if ($object instanceof ObjectIdentity) {
			$objectIdentity = $object;
		}
		else if ($object instanceof AuthorizedEntityInterface) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), $object->getAuthorizationClass());
		}
		else if ($object instanceof AuthorizedControllerInterface ) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), self::AUTHORIZED_CONTROLLER_INTERFACE);
		}
		else if ($object instanceof ApplicationConfiguration )
		{
			$objectIdentity = new ObjectIdentity($object->applicationNamespace, self::APPLICATION_CONFIGURATION_CLASS);
		}
		else {
			throw new \RuntimeException('Do not know how to get object identity from ' . get_class($object));
		}
		
		return $objectIdentity;
	}
	
	/**
	 * Constructs and returns user security identity object.
	 * @param User $user 
	 * @return UserSecurityIdentity
	 */
	private function getUserSecurityIdentity(User $user) 
	{
		return new UserSecurityIdentity($user->getId(), get_class($user));
	}
	
	/**
	 * Sets permission to $permissionStatus for $user for $object to $permission.
	 * @param User $user
	 * @param Object $object
	 * @param String $permission
	 * @param Integer $permissionStatus Shoud use constant from AuthorizationPermission class
	 */
	public function setPermsission(User $user, 
						$object, 
						$permissionName, 
						$permissionStatus) 
	{
			$userSecurityIdentity = $this->getUserSecurityIdentity($user);
			
			$acl = $this->getObjectAclForUserSecurityIdentity($userSecurityIdentity, $object);

		if (empty($acl)) {
			$objectIdentity = $this->getObjectIdentity($object);
			$acl = $this->aclProvider->createAcl($objectIdentity);
		}
		
		$permission = $this->getPermission($permissionName, $object);
		
		if ($acl instanceof Acl) {
			
			if ($permissionStatus == PermissionStatus::ALLOW) { 
				
				$currentPermissionStatus = $this->getPermissionStatus($user, $object, $permissionName);
				
				// if there was not any pervious permission, add ALLOW entry to list
				if ($currentPermissionStatus == PermissionStatus::DENY) {
					$acl->insertObjectAce($userSecurityIdentity, $permission->getMask());
				}
			}
			else if ($permissionStatus == PermissionStatus::DENY) {
				
				$aces = $acl->getObjectAces();
				
				foreach ($aces as $index => $ace) {
					
					if ($ace->getMask() == $permission->getMask() && ($ace->getSecurityIdentity() == $userSecurityIdentity)) {
						$acl->deleteObjectAce($index);
						break;
					}
				}
			}
			else {
				
				throw new Exception\RuntimeException('Bad permission value! use constants from AuthorizationPermission class!');
			}
			
			$this->aclProvider->updateAcl($acl);
		}
		else {
			throw Exception\RuntimeException('Could not create ACL for this authorizationIdentity');
		}
	}

	/**
	 * Returns permission status for $user to $objct on $permissionName.
	 * @param User $user
	 * @param Object $object
	 * @param String $permissionName
	 * @return integer
	 */
	public function getPermissionStatus(User $user, 
						$object, 
						$permissionName)
	{
		$userSecurityIdentity = $this->getUserSecurityIdentity($user);
		
		$acl = $this->getObjectAclForUserSecurityIdentity($userSecurityIdentity, $object);
		
		if (empty($acl)) {
			return PermissionStatus::DENY;
		}
		
		$permission = $this->getPermission($permissionName, $object);
		
		$aces = $acl->getObjectAces();
		
		foreach ($aces as $ace) {
			
			if ($ace instanceof \Symfony\Component\Security\Acl\Domain\Entry) {
				
				if (
							$permission->getMask() == $ace->getMask() 
							&& $ace->getSecurityIdentity() == $userSecurityIdentity
				) {
					return PermissionStatus::ALLOW;
				}
			}
		}
		
		return PermissionStatus::DENY;
	}
	
	/**
	 * Returns ACL for given object and user identity.
	 * @param mixed $object
	 * @return Acl
	 */
	private function getObjectAclForUserSecurityIdentity(UserSecurityIdentity $userSecurityIdentity, $object) 
	{
		$objectIdentity = $this->getObjectIdentity($object);
		$acl = null;
		
		try {
			$acl = $this->aclProvider->findAcl($objectIdentity, array($userSecurityIdentity));
		}
		catch (AclNotFoundException $e) { 
			// do nothing.
		}
		
		return $acl;
	}
	
	/**
	 * Returns whether the permission $permissionName is granted to user $user for object $object, 
	 * taking into account parent ACLs.
	 * @param User $user
	 * @param Object $objectIdentity
	 * @param String $permissionName
	 * @return boolean
	 */
	function isPermissionGranted(User $user, 
						$object, 
						$permissionName)
	{
		$userSecurityIdentity = $this->getUserSecurityIdentity($user);
		
		$acl = null;
		
		if ($object instanceof AuthorizedEntityInterface) {
			
			$ancestorsAndObject = array($object) + $object->getAuthorizationAncestors();
			
			foreach ($ancestorsAndObject as $o) {
				
				$oid = $this->getObjectIdentity($o);
				
				try {
					
					$acl = $this->aclProvider->findAcl($oid, array($userSecurityIdentity));
					
					break;
				}
				catch(AclNotFoundException $e) {
					// do nothing, will try next oid
				}
			}
		}
		else {
			$acl = $this->getObjectAclForUserSecurityIdentity($userSecurityIdentity, $object);
		}
		
		$permission = $this->getPermission($permissionName, $object);
		
		if ($acl instanceof Acl) {
			
			return (
						$acl->isGranted(array($permission->getMask()), array($userSecurityIdentity)) &&
						$object->authorize($user, $permissionName)
					);
		}
		else {
			return false;
		}
	}
	
	/**
	 * Sets "ALL"  access for given user to controller, revokes "SOME" and "EXECUTE" if granted.
	 * @param User $user
 	 * @param ApplicationConfiguration $applicationConfiguration
	 */
	public function grantApplicationAllAccessPermission(User $user, ApplicationConfiguration $applicationConfiguration)
	{
		$this->log->debug('Granting all application access to ' . $applicationConfiguration->id . ' for user ' . $user->getName());		
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationExecuteAccessPermission::NAME, PermissionStatus::DENY);
		$this->setPermsission($user, $applicationConfiguration, ApplicationSomeAccessPermission::NAME, PermissionStatus::DENY);
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationAllAccessPermission::NAME, PermissionStatus::ALLOW);
	}
	
	/**
	 * Grants "EXECUTE" access for given user to controler, revokes "ALL" and "SOME" access if granted.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean 
	 */
	public function grantApplicationExecuteAccessPermission(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		$this->log->debug('Granting execute application access to ' . $applicationConfiguration->id . ' for user ' . $user->getName());		
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationAllAccessPermission::NAME, PermissionStatus::DENY);
		$this->setPermsission($user, $applicationConfiguration, ApplicationSomeAccessPermission::NAME, PermissionStatus::DENY);
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationExecuteAccessPermission::NAME, PermissionStatus::ALLOW);
	}	

	/**
	 * Grants "SOME" access for givben user to controller. Revokes "ALL" and "EXECUTE" access if granted.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 */
	public function grantApplicationSomeAccessPermission(User $user, ApplicationConfiguration $applicationConfiguration)
	{
		$this->log->debug('Granting some application access to ' . $applicationConfiguration->id . ' for user ' . $user->getName());		
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationAllAccessPermission::NAME, PermissionStatus::DENY);
		$this->setPermsission($user, $applicationConfiguration, ApplicationExecuteAccessPermission::NAME, PermissionStatus::DENY);
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationSomeAccessPermission::NAME, PermissionStatus::ALLOW);
	}	
		
	/** 
	 * Revokes "ALL" access for given user to controller.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 */
	public function revokeApplicationAllAccessPermission(User $user, ApplicationConfiguration $applicationConfiguration)
	{
		$this->log->debug('Revoking all application access to ' . $applicationConfiguration->id . ' for user ' . $user->getName());		
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationAllAccessPermission::NAME, PermissionStatus::DENY);
	}
	
	/** 
	 * Revokes "SOME" access for given user to controller.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 */
	public function revokeApplicationSomeAccessPermission(User $user, ApplicationConfiguration $applicationConfiguration)
	{
		$this->log->debug('Revoking some appliaction access to ' . $applicationConfiguration->id . ' for user ' . $user->getName());		
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationSomeAccessPermission::NAME, PermissionStatus::DENY);
	}	
	
	/**
	 * Revokes "Execute" access for given user to controller.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 */
	public function revokeApplicationExecutePermission(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		$this->log->debug('Revoking application execution to ' . $applicationConfiguration->id . ' for user ' . $user->getName());
		
		$this->setPermsission($user, $applicationConfiguration, ApplicationExecuteAccessPermission::NAME, PermissionStatus::DENY);
	}
	
	/**
	 * Returns true if user has "ALL" access to controler, false otherwise.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean
	 */
	public function isApplicationAllAccessGranted(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		$permissionStatus = $this->getPermissionStatus($user, $applicationConfiguration, ApplicationAllAccessPermission::NAME);
		
		$result = $permissionStatus == PermissionStatus::ALLOW;

		$this->log->debug('Checking for all appliaction access to ' . $applicationConfiguration->id . ' for user ' . $user->getName() . ' => ' . $result);		
		
		return $result;
	}

	/** 
	 * Returns true if user has "SOME" access to controler, false otherwise.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean
	 */
	public function isApplicationSomeAccessGranted(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		$permissionStatus = $this->getPermissionStatus($user, $applicationConfiguration, ApplicationSomeAccessPermission::NAME);
		
		$result = $permissionStatus == PermissionStatus::ALLOW;

		$this->log->debug('Checking for some appliaction access to ' . $applicationConfiguration->id . ' for user ' . $user->getName() . ' => ' . $result);		
		
		return $result;
	}
	
	/**
	 * Returns true if user has controler access "Execute" granted, false otherwise.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean
	 */
	public function isApplicationExecuteAccessGranted(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		$permissionStatus = $this->getPermissionStatus($user, $applicationConfiguration, ApplicationExecuteAccessPermission::NAME);
		
		$result = $permissionStatus == PermissionStatus::ALLOW;

		$this->log->debug('Checking for execute appliaction access to ' . $applicationConfiguration->id . ' for user ' . $user->getName() . ' => ' . $result);		
		
		return $result;
	}
	
	/**
	 * Returns true if user has any access granted (EXECUTE, SOME, ALL), false otherwise.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean
	 */
	public function isApplicationAnyAccessGranted(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		return	$this->isApplicationAllAccessGranted($user, $applicationConfiguration) || 
						$this->isApplicationSomeAccessGranted($user, $applicationConfiguration) || 
						$this->isApplicationExecuteAccessGranted($user, $applicationConfiguration);
	}
	
	/**
	 * Returns true if user has admin access granted (SOME, ALL), false otherwise.
	 * @param User $user
	 * @param ApplicationConfiguration $applicationConfiguration
	 * @return boolean
	 */
	public function isApplicationAdminAccessGranted(User $user, ApplicationConfiguration $applicationConfiguration) 
	{
		return 	$this->isApplicationAllAccessGranted($user, $applicationConfiguration) ||	
						$this->isApplicationSomeAccessGranted($user, $applicationConfiguration);
	}
	
	
	/**
	 * Grants controller execute permission to user.
	 * @param User $user
	 * @param AuthorizedControllerInterface $controller 
	 */
	public function grantControllerExecutePermission(User $user, AuthorizedControllerInterface $controller) 
	{
		$this->setPermsission($user, $controller, ControllerExecutePermission::NAME, PermissionStatus::ALLOW);		
	}
	
	/**
	 * Revokes controller execution permission from user.
	 * @param User $user
	 * @param AuthorizedControllerInterface $controller 
	 */
	public function revokeControllerExecutePermission(User $user, AuthorizedControllerInterface $controller) 
	{
		$this->setPermsission($user, $controller, ControllerExecutePermission::NAME, PermissionStatus::DENY);		
	}
	
	/**
	 * Returns true if user is permitted to execute controller.
	 * @param User $user
	 * @param AuthorizedControllerInterface $controller
	 * @return boolean
	 */
	public function isControllerExecuteGranted(User $user, AuthorizedControllerInterface $controller) 
	{
		$permissionGranted = $this->isPermissionGranted($user, $controller, ControllerExecutePermission::NAME);
		
		return (
						$permissionGranted &&
						$controller->authorize($user, $this->getPermission(ControllerExecutePermission::NAME))
				);
	}
	
	/**
	 * Returns array of permission names as keys and true/false as values for all permission types registered for given object.
	 * @param User $user
	 * @param Object $object
	 * @return array
	 */
	public function getEffectivePermissionStatuses(User $user, $object) 
	{
		$permissions = $this->getPermissions($object);
		
		$result = array();
		
		foreach ($permissions as $permissionName => $permission) {
			
			$permissionGranted = $this->isPermissionGranted($user, $object, $permission);

			if ($object instanceof AuthorizedControllerInterface) {
				$permissionGranted = $permissionGranted && $object->authorize($user, $permission);
			}
			
			$result[$permissionName] = $permissionGranted;
		}
		
		return $result;
	}
}
