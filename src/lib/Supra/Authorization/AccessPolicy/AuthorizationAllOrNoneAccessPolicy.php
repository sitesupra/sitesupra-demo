<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\AbstractUser;
use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Authorization\Exception;

/**
 * Implements access policy with 2 states - "EXECUTE" and "ALL".
 */
abstract class AuthorizationAllOrNoneAccessPolicy extends AuthorizationAccessPolicyAbstraction
{
	const APPLICATION_ACCESS_NONE_VALUE = '2';
	const APPLICATION_ACCESS_ALL_VALUE = '0';

	public function configure()
	{
		parent::configure();

		$this->permission['type'] = 'SelectList';

		$this->permission['values'] = array(
				array('id' => self::APPLICATION_ACCESS_NONE_VALUE, 'title' => "{#userpermissions.label_none#}"),
				array('id' => self::APPLICATION_ACCESS_ALL_VALUE, 'title' => "{#userpermissions.label_all#}")
		);
	}

	/**
	 * Retreives current application access value for use in client side.
	 * @param AbstractUser $user
	 * @return string
	 */
	protected function getApplicationAccessValue(AbstractUser $user)
	{
		$applicationAccessValue = self::APPLICATION_ACCESS_NONE_VALUE;

		if ($this->isApplicationAllAccessGranted($user)) {
			$applicationAccessValue = self::APPLICATION_ACCESS_ALL_VALUE;
		}

		return $applicationAccessValue;
	}

	/**
	 * Sets application access for user depending on value that has been 
	 * received from client side.
	 * @param AbstractUser $user
	 * @param string $applicationAccessValue 
	 */
	protected function setApplicationAccessValue(AbstractUser $user, $applicationAccessValue)
	{
		switch ($applicationAccessValue) {

			case self::APPLICATION_ACCESS_ALL_VALUE: $this->grantApplicationAllAccessPermission($user);
				break;

			case self::APPLICATION_ACCESS_NONE_VALUE: $this->grantApplicationExecuteAccessPermission($user);
				break;

			default:
				throw new Exception\RuntimeException('Application access value "' . $applicationAccessValue . '" is not recognized.');
		}
	}

	/**
	 * Is called by Internal User Manager when user modifies some permissions.
	 * @param AbstractUser $user
	 * @param RequestInterface $request 
	 */
	public function updateAccessPolicy(AbstractUser $user, RequestInterface $request)
	{
		if ($request instanceof HttpRequest) {

			$propertyToUpdate = $request->getPostValue(self::PROPERTY_NAME);

			if ($propertyToUpdate != self::APPLICATION_ACCESS_ID) {
				throw new Exception\RuntimeException('Do not known how to update access policy property "' . $propertyToUpdate . '".');
			}

			$this->setApplicationAccessValue($user, $request->getPostValue(self::VALUE_NAME));
		}
		else {
			throw new Exception\RuntimeException('Do not known how to handle non-HttpRequests');
		}
	}

	/**
	 * Is called from Internal User Manager when loading data for some user for this application.
	 * @param AbstractUser $user
	 * @return array
	 */
	public function getAccessPolicy(AbstractUser $user)
	{
		$result = array(
				self::APPLICATION_ACCESS_ID => $this->getApplicationAccessValue($user)
		);

		return $result;
	}

}

