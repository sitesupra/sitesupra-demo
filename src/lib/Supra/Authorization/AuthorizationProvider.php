<?php

namespace Supra\Authorization;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Supra\User\Entity\AbstractUser;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\Permission\PermissionStatus;
use Supra\Authorization\Permission\Application\ApplicationAllAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationSomeAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationExecuteAccessPermission;
use Supra\Authorization\Permission\Controller\ControllerExecutePermission;
use Supra\NestedSet\Node\NodeInterface;
use Supra\Cms\ApplicationConfiguration;
use Supra\Log\Log;
use Symfony\Component\Security\Acl\Domain\Entry as Ace;

/**
 * Implements authorization provider used to check user permissions agains various 
 * objects (applications, controllers, entities)
 */
class AuthorizationProvider
{
	const AUTHORIZED_CONTROLLER_INTERFACE = 'Supra\\Authorization\\AuthorizedControllerInterface'; // you probably should not change this.
	const AUTHORIZED_ENTITY_INTERFACE = 'Supra\\Authorization\\AuthorizedEntityInterface'; // you probably should not change this.
	const APPLICATION_CONFIGURATION_CLASS = 'Supra\\Cms\\ApplicationConfiguration'; // you probably should not change this.

	const ACL_ENTRY_TABLE_NAME = 'acl_entries';

	/**
	 * @var AclProvider
	 */
	private $aclProvider;

	/**
	 * @var array
	 */
	private $permissionsByName = array();

	/**
	 * @var array
	 */
	private $permissionsByClassAndName = array();

	/**
	 * @var array
	 */
	private $permissionsByClassAndMask = array();

	/**
	 * @var Log
	 */
	private $log;

	/**
	 * @var array
	 */
	protected $applicationNamespaceAliases;

	/**
	 * Constructs AuthorizationProvider
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);

		$this->registerPermission(new ApplicationExecuteAccessPermission());
		$this->registerPermission(new ApplicationAllAccessPermission());
		$this->registerPermission(new ApplicationSomeAccessPermission());
		$this->registerPermission(new ControllerExecutePermission());

		$this->authirzedEntityAliases = array();
	}

	/**
	 * @return AclProvider
	 */
	protected function getAclProvider()
	{
		if (is_null($this->aclProvider)) {
			$entityManager = ObjectRepository::getEntityManager($this);

			$permissionGrantingStrategy = new PermissionGrantingStrategy();

			$tables = array(
				'class_table_name' => 'acl_classes',
				'entry_table_name' => self::ACL_ENTRY_TABLE_NAME,
				'oid_table_name' => 'acl_object_identities',
				'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
				'sid_table_name' => 'acl_security_identities',
			);

			$this->aclProvider = new AclProvider(
							$entityManager->getConnection(),
							$permissionGrantingStrategy,
							$tables);
		}

		return $this->aclProvider;
	}

	/**
	 * @param string $alias
	 * @param string $applicationNamespace 
	 */
	public function registerApplicationNamespaceAlias($alias, $applicationNamespace)
	{
		$this->applicationNamespaceAliases[$alias] = $applicationNamespace;
	}

	/**
	 *
	 * @param string $alias 
	 * @return string
	 */
	public function getApplicationNamespaceFromAlias($alias)
	{
		$className = null;

		if (empty($this->applicationNamespaceAliases[$alias])) {
			throw new Exception\RuntimeException('Authorized entity alias "' . $alias . '" is not known.');
		}

		$className = $this->applicationNamespaceAliases[$alias];

		return $className;
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
		$mask = $permission->getMask();

		if (isset($this->permissionsByName[$name])) {

			$existingPermission = $this->permissionsByName[$name];
			/* @var $existingPermission Permission */

			if ($existingPermission->getClass() != $class ||
					$existingPermission->getMask() != $mask
			) {
				throw new Exception\ConfigurationException('Permission type named "' . $name . '" is already registered');
			} else {
				return;
			}
		}

		if ( ! empty($this->permissionsByClassAndMask[$class][$mask])) {
			throw new Exception\ConfigurationException(
					'Permission type with mask "' . $mask . '" ' .
					'is already registered for class "' . $class . '" ' .
					'with name "' . $this->permissionsByClassAndMask[$class][$mask]->getName() . '"'
			);
		}

		$this->permissionsByName[$name] = $permission;

		if (empty($this->permissionsByClassAndName[$class])) {
			$this->permissionsByClassAndName[$class] = array();
		}

		$this->permissionsByClassAndName[$class][$name] = $permission;

		if (empty($this->permissionsByClassAndMask[$class])) {
			$this->permissionsByClassAndMask[$class] = array();
		}

		$this->permissionsByClassAndMask[$class][$mask] = $permission;
	}

	/**
	 * @param string $id
	 * @param string $class
	 * @return ObjectIdentity 
	 */
	public function createObjectIdentity($id, $class)
	{
		return new ObjectIdentity($id, $class);
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
			throw new Exception\ConfigurationException('Permission type named "' . $permissionName . '" is not registered');
		} else {

			if ( ! empty($objectToCheck)) {

				$permissionsForObject = null;

				if ($objectToCheck instanceof ObjectIdentity) {
					$permissionsForObject = $this->getPermissionsForClass($objectToCheck->getType());
				} else {
					$permissionsForObject = $this->getPermissionsForObject($objectToCheck);
				}

				if ( ! isset($permissionsForObject[$permissionName])) {

					throw new Exception\ConfigurationException('Class/superclass tree for ' . get_class($objectToCheck) .
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
	public function getPermissionsForObject($object)
	{
		return $this->getPermissionsForClass($this->getObjectIdentity($object)->getType());
	}

	/**
	 * Returns array of permission types registered for class name
	 * @param string $className class name
	 * @return array of Permission
	 */
	public function getPermissionsForClass($className)
	{
		$result = array();

		if ( ! empty($this->permissionsByClassAndName[$className])) {
			$result = $this->permissionsByClassAndName[$className];
		}

		return $result;
	}

	/**
	 * Returns permission for class and mask, if there is one
	 * @param String $class
	 * @param Integer $mask
	 * @return Permission
	 */
	public function getPermissionForClassAndMask($class, $mask)
	{
		$result = false;

		if ( ! empty($this->permissionsByClassAndMask[$class][$mask])) {
			$result = $this->permissionsByClassAndMask[$class][$mask];
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
		} else if ($object instanceof AuthorizedEntityInterface) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), $object->getAuthorizationClass());
		} else if ($object instanceof AuthorizedControllerInterface) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), self::AUTHORIZED_CONTROLLER_INTERFACE);
		} else if ($object instanceof ApplicationConfiguration) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), self::APPLICATION_CONFIGURATION_CLASS);
		} else {
			throw new Exception\ConfigurationException('Do not know how to get object identity from ' . get_class($object));
		}

		return $objectIdentity;
	}

	/**
	 * Constructs and returns user security identity object.
	 * @param AbstractUser $user 
	 * @return UserSecurityIdentity
	 */
	private function getUserSecurityIdentity(AbstractUser $user)
	{
		return new UserSecurityIdentity($user->getId(), AbstractUser::CN());
	}

	/**
	 * Returns array of Aces with matching security identity.
	 * @param UserSecurityIdentity $securityIdentity
	 * @param Acl $acl
	 * @return array
	 */
	private function getAcesForSecurityIdentity(UserSecurityIdentity $securityIdentity, Acl $acl)
	{
		$result = array();
		/* @var $ace Ace */
		foreach ($acl->getObjectAces() as $key => $ace) {

			if ($ace->getSecurityIdentity() == $securityIdentity) {
				$result[$key] = $ace;
			}
		}

		return $result;
	}

	/**
	 * Sets permission to $permissionStatus for $user for $object to $permissionName
	 * @param AbstractUser $user
	 * @param Object $object
	 * @param String $permissionName
	 * @param Integer $permissionStatus Shoud use constant from AuthorizationPermission class
	 */
	public function setPermsissionStatus(AbstractUser $user, $object, $permissionName, $newPermissionStatus)
	{
		$currentPermissionStatus = $this->getPermissionStatus($user, $object, $permissionName);

		$userSecurityIdentity = $this->getUserSecurityIdentity($user);

		/* $acl Acl */
		$acl = $this->getObjectAclForUserSecurityIdentity($userSecurityIdentity, $object);

		if (empty($acl)) {
			$objectIdentity = $this->getObjectIdentity($object);
			$acl = $this->getAclProvider()->createAcl($objectIdentity);
		}

		if (empty($acl)) {
			throw new Exception\RuntimeException('Could not create/ ACL for this object');
		}

		$permission = $this->getPermission($permissionName, $object);

		$aces = $this->getAcesForSecurityIdentity($userSecurityIdentity, $acl);
		$newAceIndex = count($acl->getObjectAces());

		if ($newPermissionStatus == PermissionStatus::ALLOW) {

			// If status is not changed, do nothing.
			if ($currentPermissionStatus == PermissionStatus::ALLOW) {
				return;
			}

			// If current status is DENY, we have to find and remove that entry.
			if ($currentPermissionStatus == PermissionStatus::DENY) {
				/* @var $ace AclEntry */
				foreach ($aces as $index => $ace) {

					if ($ace->getMask() == $permission->getDenyMask()) {
						$acl->deleteObjectAce($index);
						$newAceIndex = $index;
						break;
					}
				}
			}

			$acl->insertObjectAce($userSecurityIdentity, $permission->getAllowMask(), $newAceIndex);
		} else if ($newPermissionStatus == PermissionStatus::DENY) {

			// If status is not changed, do nothing.
			if ($currentPermissionStatus == PermissionStatus::DENY) {
				return;
			}

			if ($currentPermissionStatus == PermissionStatus::ALLOW) {

				/* @var $ace AclEntry */
				foreach ($aces as $index => $ace) {

					if ($ace->getMask() == $permission->getAllowMask()) {
						$acl->deleteObjectAce($index);
						$newAceIndex = $index;
						break;
					}
				}
			}

			$acl->insertObjectAce($userSecurityIdentity, $permission->getDenyMask(), $newAceIndex);
		} else if ($newPermissionStatus == PermissionStatus::NONE) {

			// If current permission status is not NONE, there is Ace 
			// entry (ALLOW or DENY) which has to be removed.
			if ($currentPermissionStatus != PermissionStatus::NONE) {

				foreach ($aces as $index => $ace) {

					if (
							$ace->getMask() == $permission->getDenyMask() ||
							$ace->getMask() == $permission->getAllowMask()
					) {

						$acl->deleteObjectAce($index);
						break;
					}
				}
			}
		} else {
			throw new Exception\ConfigurationException('Bad permission status value! use constants from AuthorizationPermission class!');
		}

		$this->getAclProvider()->updateAcl($acl);

		///$this->log->debug('AAAAAAAAAA Set ' . $permissionName . ' to ' . $permissionStatus . ' for ' . $object);
	}

	/**
	 * Returns permission status for $user to $objct on $permissionName.
	 * @param AbstractUser $user
	 * @param Object $object
	 * @param String $permissionName
	 * @return integer
	 */
	public function getPermissionStatus(AbstractUser $user, $object, $permissionName)
	{
		$userSecurityIdentity = $this->getUserSecurityIdentity($user);

		$acl = $this->getObjectAclForUserSecurityIdentity($userSecurityIdentity, $object);

		$result = PermissionStatus::NONE;

		if ( ! empty($acl)) {

			$permission = $this->getPermission($permissionName, $object);

			$aces = $acl->getObjectAces();

			//\Log::debug('P ALLOW MASK: ', $permission->getAllowMask());
			//\Log::debug('P DENY MASK : ', $permission->getDenyMask());

			foreach ($aces as $ace) {

				//\Log::debug('ACE MASK:     ', $ace->getMask());
				//\Log::debug('ACE SID:      ', $ace->getSecurityIdentity());
				//\Log::debug('SID:          ', $userSecurityIdentity);

				if (
						($ace instanceof \Symfony\Component\Security\Acl\Domain\Entry) &&
						($ace->getSecurityIdentity() == $userSecurityIdentity)
				) {

					if ($ace->getMask() == $permission->getAllowMask()) {

						$result = PermissionStatus::ALLOW;
						break;
					} else if ($ace->getMask() == $permission->getDenyMask()) {

						$result = PermissionStatus::DENY;
						break;
					}
				}
			}
		}

		//$this->log->debug('AAAAAAAAAA Get ' . $permissionName . ' status for ' . $user->getName() . ' to ' . $this->getObjectIdentity($object) . ' => ' . $result);

		return $result;
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
			$acls = $this->getAclProvider()->findAcls(array($objectIdentity));
			$acl = $acls->offsetGet($objectIdentity);
		} catch (AclNotFoundException $e) {
			// do nothing.
		}

		return $acl;
	}

	/**
	 * Returns whether the permission $permissionName is granted to user $user for object $object, 
	 * taking into account parent ACLs.
	 * @param AbstractUser $user
	 * @param Object $objectIdentity
	 * @param String $permissionName
	 * @param boolean $checkGroup
	 * @return boolean
	 */
	function isPermissionGranted(AbstractUser $user, $object, $permissionName, $checkGroup = true)
	{
		if ($user->isSuper()) { // ... this is dirty.
			return true;
		}

		if ($object instanceof AuthorizedEntityInterface) {

			$ancestorsAndObject = $object->getAuthorizationAncestors();
			array_unshift($ancestorsAndObject, $object);

			foreach ($ancestorsAndObject as $o) {

				$permissionStatus = $this->getPermissionStatus($user, $o, $permissionName);

				if ($permissionStatus == PermissionStatus::ALLOW) {
					return true;
				} else if ($permissionStatus == PermissionStatus::DENY) {
					return false;
				}
			}
		} else {
			$permissionStatus = $this->getPermissionStatus($user, $object, $permissionName);

			if ($permissionStatus == PermissionStatus::ALLOW) {
				return true;
			} else if ($permissionStatus == PermissionStatus::DENY) {
				return false;
			}
		}

		if (
				$checkGroup &&
				$user instanceof User &&
				( ! is_null($user->getGroup()))
		) {
			return $this->isPermissionGranted($user->getGroup(), $object, $permissionName);
		}

		return false;
	}

	/**
	 * Returns array of permission names as keys and true/false as values for all permission types registered for given class.
	 * @param AbstractUser $user
	 * @param Object $object
	 * @return array
	 */
	public function getEffectivePermissionStatusesByObjectClass(AbstractUser $user, $class)
	{
		$classOids = $this->getAclProvider()->getOidsByClass($class);

		$acls = $this->getAclProvider()->findAcls($classOids);

		$userSecurityIdentity = $this->getUserSecurityIdentity($user);

		/* keys will be oid ids */
		$results = array();

		$permissionNamesForClass = array_keys($this->getPermissionsForClass($class));

		$defaultDenyAllRow = array_fill_keys($permissionNamesForClass, PermissionStatus::NONE);

		foreach ($acls as $oid) {

			$resultRow = $defaultDenyAllRow;

			if ($oid instanceof ObjectIdentity) {

				$acl = $acls->offsetGet($oid);

				if ($acl instanceof Acl) {

					$aces = $acl->getObjectAces();

					foreach ($aces as $ace) {

						if ($ace->getSecurityIdentity() != $userSecurityIdentity) {
							continue;
						}

						$permission = $this->getPermissionForClassAndMask($class, $ace->getMask() & (Permission\Permission::ALLOW_MASK - 1));

						if ($permission) {
							if ($permission->getAllowMask() == $ace->getMask()) {
								$resultRow[$permission->getName()] = PermissionStatus::ALLOW;
							} else if ($permission->getDenyMask() == $ace->getMask()) {
								$resultRow[$permission->getName()] = PermissionStatus::DENY;
							}
						}
					}
				}
			}

			$results[$oid->getIdentifier()] = $resultRow;
		}

		return $results;
	}

	public function getPermissionStatusesForAuthorizedEntity(AbstractUser $user, $object)
	{
		$objectIdentity = $this->getObjectIdentity($object);

		$class = $objectIdentity->getType();

		$permissionNamesForClass = array_keys($this->getPermissionsForClass($class));

		$results = array();

		foreach ($permissionNamesForClass as $permissionName) {

			$results[$permissionName] = $this->isPermissionGranted($user, $object, $permissionName);
		}

		return $results;
	}

	/**
	 * Grants controller execute permission to user.
	 * @param AbstractUser $user
	 * @param AuthorizedControllerInterface $controller 
	 */
	public function grantControllerExecutePermission(AbstractUser $user, AuthorizedControllerInterface $controller)
	{
		$this->setPermsissionStatus($user, $controller, ControllerExecutePermission::NAME, PermissionStatus::ALLOW);
	}

	/**
	 * Revokes controller execution permission from user.
	 * @param AbstractUser $user
	 * @param AuthorizedControllerInterface $controller 
	 */
	public function revokeControllerExecutePermission(AbstractUser $user, AuthorizedControllerInterface $controller)
	{
		$this->setPermsissionStatus($user, $controller, ControllerExecutePermission::NAME, PermissionStatus::DENY);
	}

	/**
	 * Returns true if user is permitted to execute controller.
	 * @param AbstractUser $user
	 * @param AuthorizedControllerInterface $controller
	 * @return boolean
	 */
	public function isControllerExecuteGranted(AbstractUser $user, AuthorizedControllerInterface $controller)
	{
		$permissionGranted = $this->isPermissionGranted($user, $controller, ControllerExecutePermission::NAME);

		return (
				$permissionGranted &&
				$controller->authorize($user, $this->getPermission(ControllerExecutePermission::NAME))
				);
	}

	/**
	 * Removes all user's individual permissions
	 * @param AbstractUser $user
	 */
	public function unsetAllUserPermissions(AbstractUser $user)
	{
		$sid = $this->getUserSecurityIdentity($user);
		$this->getAclProvider()->removeSidAces($sid);
	}

}
