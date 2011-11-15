<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\AbstractUser;

/**
 * Implements access policy with 3 states - "EXECUTE", "SOME", "ALL"
 */
class AuthorizationThreewayAccessPolicy extends AuthorizationAllOrNoneAccessPolicy
{
	const APPLICATION_ACCESS_SOME_VALUE = '1';

	/**
	 * {@inheritDoc}
	 */
	public function configure()
	{
		parent::configure();

		$this->permission['type'] = 'Dial';

		// Insert state "Some" to be shown between "None" and "All"
		array_splice($this->permission['values'], 1, 0, array(array(
						'id' => self::APPLICATION_ACCESS_SOME_VALUE,
						'title' => "{#userpermissions.label_some#}"
				))
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getApplicationAccessValue(AbstractUser $user)
	{
		$applicationAccessValue = parent::getApplicationAccessValue($user);

		if ($applicationAccessValue == self::APPLICATION_ACCESS_NONE_VALUE) {

			if ($this->isApplicationSomeAccessGranted($user)) {
				$applicationAccessValue = self::APPLICATION_ACCESS_SOME_VALUE;
			}
		}

		return $applicationAccessValue;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function setApplicationAccessValue(AbstractUser $user, $applicationAccessValue)
	{
		if ($applicationAccessValue == self::APPLICATION_ACCESS_SOME_VALUE) {
			$this->grantApplicationSomeAccessPermission($user);
		}
		else {
			parent::setApplicationAccessValue($user, $applicationAccessValue);
		}
	}

}