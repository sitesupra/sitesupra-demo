<?php

namespace Supra\Authentication\Adapter;

use Supra\User\Entity\User;
use Supra\Authentication\Exception;
use Supra\Authentication\AuthenticationPassword;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;

/**
 * Adapter with email as login and password sha1 hash validation
 */
class HashAdapter implements AuthenticationAdapterInterface
{
	/**
	 * Hashing algorithm used
	 * @var string
	 */
	private $algorithmClass = 'Supra\Authentication\Adapter\Algorithm\BlowfishAlgorithm';
	
	/**
	 * @var Algorithm\CryptAlgorithm
	 */
	private $algorithm;
	
	/**
     * Domain name allows make authentication simply with username 
     * @example username: mycoolusername. Result mycoolusername@defaultdomain.lv
	 * @var string 
	 */
	private $defaultDomain;
	
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
	 * @return Algorithm\CryptAlgorithm
	 */
	protected function getAlgorythm()
	{
		if (is_null($this->algorithm)) {
			$this->algorithm = Loader::getClassInstance($this->algorithmClass,
					Algorithm\CryptAlgorithm::CN);
		}
		
		return $this->algorithm;
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
		$userPassword = $user->getPassword();
		
		$valid = $this->getAlgorythm()
				->validate($password, $userPassword, $salt);
		
		if ( ! $valid) {
			throw new Exception\WrongPasswordException("Hashing algorithm validation failed");
		}
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
			$passHash = $this->getAlgorythm()->crypt($password, $salt);
			$user->setPassword($passHash);
		}
		
		// Flush automatically
		$userProvider = ObjectRepository::getUserProvider($this);
		//$userProvider->getEntityManager()
				//->flush();
		$userProvider->updateUser($user);
	}
	
	/**
	 * {@inheritdoc}
	 * @param string $login
	 * @return string 
	 */
	public function getFullLoginName($login)
	{
		$domain = $this->getDefaultDomain();
		
		if (empty($domain)) {
			return $login;
		}
		
		// check if current login is an email
		$isEmail = strpos($login, '@');
		if ($isEmail !== false) {
			return $login;
		}
		
		$login = $login . '@' . $this->getDefaultDomain();
		
		return $login;
	}

	/**
	 * @return string
	 */
	public function getDefaultDomain()
	{
		return $this->defaultDomain;
	}

	/**
	 * @param string $defaultDomain 
	 */
	public function setDefaultDomain($defaultDomain)
	{
		$this->defaultDomain = trim($defaultDomain, '@');
	}

}
