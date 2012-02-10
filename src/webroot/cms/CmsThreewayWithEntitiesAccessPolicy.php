<?php

namespace Supra\Cms;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\Authorization\Permission\PermissionStatus;
use Supra\User\Entity\Group;
use Supra\User\Entity\User;
use Supra\User\Entity\AbstractUser;
use Supra\Validator\FilteredInput;

abstract class CmsThreewayWithEntitiesAccessPolicy extends AuthorizationThreewayWithEntitiesAccessPolicy
{
	const PERMISSION_NAME_ALLOW_NOTHING = 'allow_nothing';

	protected $permissionHierarchy;

	public function configure()
	{
		$this->permissionHierarchy = array();

		parent::configure();

		$allowNothingSubproperty = array(
			'id' => self::PERMISSION_NAME_ALLOW_NOTHING,
			'title' => '{#userpermissions.label_' . self::PERMISSION_NAME_ALLOW_NOTHING . '#}'
		);
		array_unshift($this->permission['subproperty']['values'], $allowNothingSubproperty);

		$this->permission['subproperty']['multiple'] = false;
	}

	/**
	 * @param Group $group
	 * @param string $entityId
	 * @param FilteredInput $setPermissionNames 
	 */
	protected function setEntityPermissionsForGroup(Group $group, $entityId, FilteredInput $setPermissionNames)
	{
		parent::setEntityPermissionsForGroup($group, $entityId, $setPermissionNames);
	}

	public function updateAccessPolicy(AbstractUser $user, FilteredInput $input)
	{
		// If we have data for entity (Page, File) update, update that ...
		if ($input->hasChild(self::ENTITIES_LIST_ID)) {

			$updateData = $input->getChild(self::ENTITIES_LIST_ID);

			$entityId = $updateData->get(self::ENTITY_ID);

			$setPermissionNames = new FilteredInput();
			if ($updateData->has(self::SET_PERMISSION_ID)) {
				$setPermissionNames->append($updateData->get(self::SET_PERMISSION_ID));
			}

			$this->setEntityPermissions($user, $entityId, $setPermissionNames);
		} else {
			// ... otherwise this update is for application access, update that.
			parent::updateAccessPolicy($user, $input);
		}
	}

	/**
	 * @param User $user
	 * @param string $entityId
	 * @param FilteredInput $setPermissionNames 
	 */
	protected function setEntityPermissionsForUser(User $user, $entityId, FilteredInput $setPermissionNames)
	{
		// Just a shorthand.
		$ap = $this->ap;

		// Creating surrogate ObjectIdentity, as we do not need anything else and 
		// lookup in some repo costs and even might be not trivial if actual class 
		// differs from authorizationClass.
		$oid = $ap->createObjectIdentity($entityId, $this->subpropertyClass);

		// There should be only one or none;
		$setPermissionNames->rewind();

		// If no permission name is sent, it means user has clicked TrashCan 
		// for this permission entry.
		if ($setPermissionNames->count() == 0) {

			foreach ($this->subpropertyPermissionNames as $permissionName) {
				$ap->setPermissionStatusDirect($user, $oid, $permissionName, PermissionStatus::INHERIT);
			}
		} else {

			$setPermissionName = $setPermissionNames->getNext();

			// Check if "ALLOW NOTHING" has been activated...
			if ($setPermissionName == self::PERMISSION_NAME_ALLOW_NOTHING) {

				// This means permissions need to be set up so that any permission 
				// will be denied, either by adding DENY ACEs or inheriting them from 
				// user's group.

				foreach ($this->subpropertyPermissionNames as $permissionName) {
					$ap->setPermissionStatusDirectWithGroup($user, $oid, $permissionName, PermissionStatus::DENY);
				}
			} else {

				$dependingPermissionNames = $this->permissionHierarchy[$setPermissionName];

				foreach ($dependingPermissionNames as $dependingPermissionName) {
					$ap->setPermissionStatusDirectWithGroup($user, $oid, $dependingPermissionName, PermissionStatus::ALLOW);
				}

				foreach ($this->subpropertyPermissionNames as $permissionName) {

					if ( ! in_array($permissionName, $dependingPermissionNames)) {
						$ap->setPermissionStatusDirectWithGroup($user, $oid, $permissionName, PermissionStatus::DENY);
					}
				}
			}
		}
	}

}

