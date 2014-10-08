<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Database\Entity;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\AuthorizationProvider;
use Supra\Database\Doctrine\Listener\Timestampable;
use DateTime;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"user" = "User", "group" = "Group"})
 * @Table(name="user_abstraction", indexes={
 *		@index(name="user_abstraction_name_idx", columns={"name"})
 * })
 * @HasLifecycleCallbacks
 */
abstract class AbstractUser extends Entity implements AuthorizedEntityInterface//, Timestampable
{
	const PERMISSION_MODIFY_USER_NAME = 'modify_user';
	const PERMISSION_MODIFY_USER_MASK = 256;
	
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns creation time
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time to now
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->modificationTime = $time;
	}

	/**
	 * Returns whener the user/group has SUPER privileges.
	 * @return boolean
	 */
	abstract function isSuper();
	
	public function authorize(AbstractUser $user, $permission, $grant) 
	{
		return true;
	}
	
	public function getAuthorizationId() 
	{
		return $this->getId();
	}
	
	public static function getAuthorizationClass()
	{
		$className = self::CN();
		
		return $className;
	}
	
	public function getAuthorizationAncestors() 
	{
		return array();
	}

	public static function registerPermissions(AuthorizationProvider $ap) 
	{
		$ap->registerGenericEntityPermission(
				self::PERMISSION_MODIFY_USER_NAME, 
				self::PERMISSION_MODIFY_USER_MASK, 
				self::CN()
		);
	}	
}