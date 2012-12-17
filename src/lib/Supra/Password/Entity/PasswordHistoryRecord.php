<?php

namespace Supra\Password\Entity;

use Supra\Database\Entity;
use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Loader\Loader;
use Supra\Authentication\Adapter\Algorithm\CryptAlgorithm;

/**
 * @Entity
 */
class PasswordHistoryRecord extends Entity
{
	
	/**
	 * Hashing algorithm used
	 * @var string
	 */
	private $algorithmClass = 'Supra\Authentication\Adapter\Algorithm\BlowfishAlgorithm';
	
	/**
	 * @Column(type="supraId20", nullable=false)
	 * 
	 * @var string
	 */
	protected $userId;
	
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $hash;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $salt;
	
	/**
	 * 
	 */
	public function __construct(User $user = null)
	{
		parent::__construct();
		
		if ( ! is_null($user)) {
			$this->hash = $user->getPassword();
			$this->salt = $user->getSalt();
			
			$this->userId = $user->getId();
		}
		
		$this->creationTime = new \DateTime('now');
	}
	
	/**
	 * @return Algorithm\CryptAlgorithm
	 */
	protected function getAlgorithm()
	{
		if (is_null($this->algorithm)) {
			$this->algorithm = Loader::getClassInstance($this->algorithmClass,
					CryptAlgorithm::CN);
		}
		
		return $this->algorithm;
	}
	
	/**
	 * 
	 * @param User $user
	 */
	public function setUser(User $user)
	{
		$this->userId = $user->getId();
	}
	
	/**
	 * @param string $hash
	 */
	public function setHash($hash)
	{
		$this->hash = $hash;
	}
	
	/**
	 * @param string $salt
	 */
	public function setSalt($salt)
	{
		$this->salt = $salt;
	}
	
	/**
	 * 
	 * @param AuthenticationPassword $password
	 * @return boolean
	 */
	public function isEquals(AuthenticationPassword $password)
	{
		$valid = $this->getAlgorithm()
				->validate($password, $this->hash, $this->salt);
		
		return ($valid === true);
	}
	
}