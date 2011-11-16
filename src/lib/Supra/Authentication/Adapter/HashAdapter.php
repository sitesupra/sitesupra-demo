<?php

namespace Supra\Authentication\Adapter;

use Supra\User\Entity\User;
use Supra\Authentication\Exception;
use Supra\Authentication\AuthenticationPassword;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Adapter with email as login and password sha1 hash validation
 */
class HashAdapter implements AuthenticationAdapterInterface
{
	/**
	 * Hashing method used
	 * @var string
	 */
	private $algorithm = 'sha1';
	
	/**
	 * Whether to use binary hash value
	 * @var boolean
	 */
	private $rawOutput = false;
	
	/**
	 * Finds user in database
	 * @param string $login
	 * @param AuthenticationPassword $password
	 * @return User 
	 */
	public function findUser($login, AuthenticationPassword $password)
	{
		
	}

	/**
	 * Authenticates user
	 * @param User $user
	 * @param AuthenticationPassword $password
	 * @throws Exception\AuthenticationFailure on failures
	 */
	public function authenticate(User $user, AuthenticationPassword $password)
	{
		$salt = $user->getSalt();
		$hash = $this->generatePasswordHash($password, $salt);
		
		$userPassword = $user->getPassword();
		
		if($hash != $userPassword) {
			throw new Exception\WrongPasswordException();
		}
	}
		
	/**
	 * Generates password for database
	 * @param AuthenticationPassword $password
	 * @param string $salt
	 * @return string
	 */
	protected function generatePasswordHash(AuthenticationPassword $password, $salt)
	{
		if (empty($salt)) {
			throw new Exception\RuntimeException("User password salt is not permitted to be empty");
		}
		
		$hash = hash_hmac($this->algorithm, (string) $password, $salt, $this->rawOutput);
		
		return $hash;
	}
	
	/**
	 * {@inheritdoc}
	 * @param User $user
	 * @param AuthenticationPassword $password
	 */
	public function credentialChange(User $user, AuthenticationPassword $password = null)
	{
		// Email is login for this adapter
		$user->setLogin($user->getEmail());
		
		if ( ! is_null($password)) {
			
			if ($password->isEmpty()) {
				throw new Exception\PasswordPolicyException("Empty password is not allowed");
			}
			
			$salt = $user->resetSalt();
			$passHash = $this->generatePasswordHash($password, $salt);
			$user->setPassword($passHash);
		}
		
		// Flush automatically
		$userProvider = ObjectRepository::getUserProvider($this);
		$userProvider->getEntityManager()
				->flush();
	}

}