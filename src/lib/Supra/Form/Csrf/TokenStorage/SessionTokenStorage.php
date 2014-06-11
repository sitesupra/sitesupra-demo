<?php

namespace Supra\Form\Csrf\TokenStorage;

use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Supra\Session\SessionNamespace;

/**
 * Supra's Session Token Storage
 */
class SessionTokenStorage implements TokenStorageInterface
{
	/**
	 * @var \Supra\Session\SessionNamespace;
	 */
	protected $sessionNamespace;

	/**
	 * @param \Supra\Session\SessionNamespace $sessionNamespace
	 */
	public function __construct(SessionNamespace $sessionNamespace)
	{
		$this->sessionNamespace = $sessionNamespace;
	}

	public function getToken($tokenId)
	{
		if ( ! $this->sessionNamespace->__isset($tokenId)) {
			throw new TokenNotFoundException("The CSRF token with ID {$tokenId} does not exist.");
        }

        return (string) $this->sessionNamespace->__get($tokenId);
	}

	public function hasToken($tokenId)
	{
		return $this->sessionNamespace->__isset($tokenId);
	}

	public function removeToken($tokenId)
	{
		$this->sessionNamespace->__unset($tokenId);
	}

	public function setToken($tokenId, $token)
	{
		$this->sessionNamespace->__set($tokenId, $token);
	}
}