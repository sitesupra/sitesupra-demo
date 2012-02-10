<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\Permission\PermissionStatus;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AuthorizationProvider;
use Supra\User\Entity\AbstractUser;
use Supra\User\Entity\Group;
use Supra\User\Entity\User;
use Supra\Authorization\Permission\Permission;
use Supra\Validator\FilteredInput;
use Doctrine\ORM\EntityManager;
use Supra\Authorization\AuthorizedEntityInterface;

/**
 * Class provides abstraction for access policies for managers with authorized entities (Pages, Files).
 */
abstract class AuthorizationThreewayWithEntitiesAccessPolicy extends AuthorizationThreewayAccessPolicy
{
	const ENTITIES_LIST_ID = 'list';
	const ENTITY_ID = 'id';
	const SET_PERMISSIONS_ID = 'values';
	const SET_PERMISSION_ID = 'value';
	const ENTITIES_ITEMS_ID = 'items';

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var string
	 */
	protected $subpropertyClass;

	/**
	 * @var string
	 */
	protected $subpropertyLabel;

	/**
	 * @var array
	 */
	protected $subpropertyPermissionNames;

	public function __construct($subpropertyLabel, $subpropertyClass)
	{
		parent::__construct();

		$this->subpropertyClass = $subpropertyClass;
		$this->subpropertyLabel = $subpropertyLabel;
	}

	public function configure()
	{
		parent::configure();

		$ap = $this->ap;
		
		$subpropertyClass = $this->subpropertyClass;
		$subpropertyClass::registerPermissions($ap);

		$permissionCheckAlias = $subpropertyClass::getAlias();
		$ap->registerApplicationNamespaceAlias($permissionCheckAlias, $this->applicationNamespace);

		$this->subpropertyPermissionNames = array_keys($ap->getPermissionsForClass($subpropertyClass));

		$subpropertyValues = array();
		foreach ($this->subpropertyPermissionNames as $name) {
			$subpropertyValues[] = array('id' => $name, 'title' => "{#userpermissions.label_" . $name . "#}");
		}

		$this->permission['sublabel'] = $this->subpropertyLabel;
		$this->permission['subproperty'] = array(
			'id' => 'permissions',
			'type' => 'SelectList',
			'multiple' => true,
			'values' => $subpropertyValues
		);
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	public function updateAccessPolicy(AbstractUser $user, FilteredInput $input)
	{
		// If we have data for entity (Page, File) update, update that ...
		if ($input->hasChild(self::ENTITIES_LIST_ID)) {

			$updateData = $input->getChild(self::ENTITIES_LIST_ID);

			$entityId = $updateData->get(self::ENTITY_ID);

			$setPermissionNames = null;
			if ($updateData->hasChild(self::SET_PERMISSION_ID)) {
				$setPermissionNames = $updateData->getChild(self::SET_PERMISSION_ID);
			} else {
				$setPermissionNames = new FilteredInput();
			}

			$this->setEntityPermissions($user, $entityId, $setPermissionNames);
		} else {
			// ... otherwise this update is for application access, update that.
			parent::updateAccessPolicy($user, $input);
		}
	}

	public function getAccessPolicy(AbstractUser $user)
	{
		$result = parent::getAccessPolicy($user);

		$result[self::ENTITIES_ITEMS_ID] = $this->getAllEntityPermissionsArray($user);

		return $result;
	}

	protected function getAllEntityPermissionStatuses(AbstractUser $user)
	{
		$allEntityPermissionStatusesFromGroup = array();

		if ( ! ($user instanceof Group)) {
			$allEntityPermissionStatusesFromGroup = $this->getAllEntityPermissionStatuses($user->getGroup());
		}

		$allEntityPermissionStatusesFromUser = $this->ap->getEffectivePermissionStatusesByObjectClass($user, $this->subpropertyClass);

		$allEntityIds = array_unique(array_keys($allEntityPermissionStatusesFromGroup) + array_keys($allEntityPermissionStatusesFromUser));

		$result = array();

		foreach ($allEntityIds as $entityId) {

			$entityPermissionStatuses = array();

			if ( ! empty($allEntityPermissionStatusesFromGroup[$entityId])) {
				$entityPermissionStatuses = $allEntityPermissionStatusesFromGroup[$entityId];
			}

			if ( ! empty($allEntityPermissionStatusesFromUser[$entityId])) {

				if (empty($entityPermissionStatuses)) {
					$entityPermissionStatuses = $allEntityPermissionStatusesFromUser[$entityId];
				} else {

					foreach ($allEntityPermissionStatusesFromUser[$entityId] as $permissionName => $status) {
						if ($status != PermissionStatus::INHERIT) {
							$entityPermissionStatuses[$permissionName] = $status;
						}
					}
				}
			}

			$result[$entityId] = $entityPermissionStatuses;
		}

		return $result;
	}

	/**
	 * Return array with item permissions for authorized entities for this application for client side.
	 * @param AbstractUser $user
	 * @return array
	 */
	public function getAllEntityPermissionsArray(AbstractUser $user)
	{
		$result = array();

		$allEntityPermissionStatuses = $this->getAllEntityPermissionStatuses($user);

		foreach ($allEntityPermissionStatuses as $entityId => $permissionStatuses) {

			$allowed = array();
			$denied = array();

			foreach ($permissionStatuses as $name => $status) {

				switch ($status) {
					case PermissionStatus::ALLOW: $allowed[$name] = $name;
						break;
					case PermissionStatus::DENY: $denied[$name] = $name;
						break;
					default: continue;
				}
			}

			$resultRow = $this->getEntityPermissionArray($user, $entityId, $allowed, $denied);

			if ( ! empty($resultRow)) {
				$result[] = $resultRow;
			}
		}

		return $result;
	}

	/**
	 * Returns entity permission array to be sent to client side.
	 * @param AbstractUser $user
	 * @param string $entityId
	 * @param array $grantedPermissionNames
	 * @param array $forbidenPermissionNames
	 * @return array 
	 */
	protected function getEntityPermissionArray(AbstractUser $user, $entityId, $allowedPermissionNames, $deniedPermissionNames)
	{
		$result = array();

		if ( ! empty($allowedPermissionNames)) {
			$result = array(self::ENTITY_ID => $entityId, self::SET_PERMISSION_ID => array_values($allowedPermissionNames));
		}

		return $result;
	}

	/**
	 * Sets authorized entity permission
	 * @param AbstractUser $user
	 * @param string $entityId
	 * @param FilteredInput $setPermissionNames 
	 */
	public function setEntityPermissions(AbstractUser $user, $entityId, FilteredInput $setPermissionNames)
	{
		if ($user instanceof User) {
			return $this->setEntityPermissionsForUser($user, $entityId, $setPermissionNames);
		} else if ($user instanceof Group) {
			return $this->setEntityPermissionsForGroup($user, $entityId, $setPermissionNames);
		}
	}

	protected function setEntityPermissionsForUser(User $user, $entityId, FilteredInput $setPermissionNames)
	{
		// Creating surrogate ObjectIdentity, as we do not need anything else and 
		// lookup in some repo costs and even might be not trivial if actual class 
		// differs from authorizationClass.
		$oid = $this->ap->createObjectIdentity($entityId, $this->subpropertyClass);

		// Check all permission names defined for this authorized entity so we can 
		// set permissions received from client side and un-set those not received.
		foreach ($this->subpropertyPermissionNames as $permissionName) {

			$currentPermissionStatusInGroup = $this->ap->getPermissionStatus($user->getGroup(), $oid, $permissionName);

			// If permission is marked as "set" for this authorized entity ...
			if ($setPermissionNames->contains($permissionName)) {

				// ... and group of user does not have anything for this entitiy ...
				if ($currentPermissionStatusInGroup == PermissionStatus::INHERIT) {
					// ... set ALLOW for user on entity.
					$this->ap->setPermsissionStatus($user, $oid, $permissionName, PermissionStatus::ALLOW);
				} else if ($currentPermissionStatusInGroup == PermissionStatus::ALLOW) {
					// ... if permission in group is ALLOW, we can unset any permission 
					// for this user on entity.
					$this->ap->setPermsissionStatus($user, $oid, $permissionName, PermissionStatus::INHERIT);
				} else {
					// ... for now we do nothing if status in users group for this entity 
					// is something else.
				}
			} else {

				// If it is not marked as "set" (i.e. - button is rised) ...
				if ($currentPermissionStatusInGroup == PermissionStatus::ALLOW) {
					// ... if permission in group is ALLOW, we have to set status for user 
					// on this entity as DENY.
					$this->ap->setPermsissionStatus($user, $oid, $permissionName, PermissionStatus::DENY);
				} else if ($currentPermissionStatusInGroup == PermissionStatus::INHERIT) {
					$this->ap->setPermsissionStatus($user, $oid, $permissionName, PermissionStatus::INHERIT);
				}
			}
		}
	}

	protected function setEntityPermissionsForGroup(Group $group, $entityId, FilteredInput $setPermissionNames)
	{
		// Creating surrogate ObjectIdentity, as we do not need anything else and 
		// lookup in some repo costs and even might be not trivial if actual class 
		// differs from authorizationClass.
		$oid = $this->ap->createObjectIdentity($entityId, $this->subpropertyClass);

		$userProvider = ObjectRepository::getUserProvider($this);
		$users = $userProvider->getAllUsersInGroup($group);

		// Check all permission names defined for this authorized entity so we can 
		// set permissions received from client side and un-set those not received.
		foreach ($this->subpropertyPermissionNames as $permissionName) {

			// If permission is marked as "set" for this authorized entity ...
			if ($setPermissionNames->contains($permissionName)) {

				// ... set ALLOW.
				$this->ap->setPermsissionStatus($group, $oid, $permissionName, PermissionStatus::ALLOW);
			} else {

				// ... if permission name is not marked, set it to NONE ...
				$this->ap->setPermsissionStatus($group, $oid, $permissionName, PermissionStatus::INHERIT);

				// ... and set to NONE any user permissions that have DENY on this entity
				// as only way they could have DENY is if some permission granted by group 
				// was revoked for user.

				foreach ($users as $user) {
					/* @var $user User */
					$currentStatus = $this->ap->getPermissionStatus($user, $oid, $permissionName);

					if ($currentStatus == PermissionStatus::DENY) {

						$this->ap->setPermsissionStatus($user, $oid, $permissionName, PermissionStatus::INHERIT);
					}
				}
			}
		}
	}

	abstract public function getEntityTree(FilteredInput $input);

	/**
	 * @param string $id 
	 * @return AuthorizedEntityInterface
	 */
	public function getAuthorizedEntityFromId($id)
	{
		$em = $this->getEntityManager();

		$repo = $em->getRepository($this->subpropertyClass);

		$entity = $repo->find($id);

		return $entity;
	}

	/**
	 * @param AbstractUser $user
	 * @param AuthorizedEntityInterface $entity
	 * @return array
	 */
	public function getPermissionStatusesForAuthorizedEntity(AbstractUser $user, $entity)
	{
		$ap = $this->ap;
		
		$permissions = $ap->getPermissionsForObject($entity);
		$permissionNames = array_keys($permissions);
		
		$allAccessGranted = $this->isApplicationAllAccessGranted($user);
		$someAccessGranted = $this->isApplicationSomeAccessGranted($user);
		
		$result = array_combine($permissionNames, array_fill(0, count($permissionNames), false));
		
		if($allAccessGranted) {
			$result = array_combine($permissionNames, array_fill(0, count($permissionNames), true));
		}
		elseif($someAccessGranted) {
			$result = $ap->getPermissionStatusesForAuthorizedEntity($user, $entity);
		}

		return $result;
	}
}
