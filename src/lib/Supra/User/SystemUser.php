<?php

namespace Supra\User;

/**
 * @HasLifecycleCallbacks
 */
class SystemUser extends Entity\User
{
	const LOGIN = 'system';

	public function __construct()
	{
		parent::__construct();

		$this->setLogin(self::LOGIN);
		$this->setName('System');
	}

	/**
	 * @prePersist
	 */
	public function failPerist()
	{
		// Don't persist me, bro!
		throw new Exception\RuntimeException('Must not persist user "system".');
	}

}
