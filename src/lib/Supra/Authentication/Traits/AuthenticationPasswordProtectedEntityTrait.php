<?php

namespace Supra\Authentication\Traits;

trait AuthenticationPasswordProtectedEntityTrait
{
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $password;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $salt;

	/**
	 * @return string
	 */
	public function getPasswordHash()
	{
		return $this->password;
	}

	/**
	 * @param string $hash
	 */
	public function setPasswordHash($hash)
	{
		$this->password = $hash;
	}

	/**
	 * @return bool
	 */
	public function hasPassword()
	{
		return $this->password !== null;
	}

	/**
	 * @return string
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * Resets salt and returns
	 * @return string
	 */
	public function resetSalt()
	{
		// Generates 23 character salt
		$this->salt = uniqid('', true);
		return $this->salt;
	}
}
