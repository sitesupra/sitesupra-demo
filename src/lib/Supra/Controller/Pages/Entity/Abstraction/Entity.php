<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\User\Entity\AbstractUser;
use Supra\Authorization\Permission\Permission;
use Supra\Authorization\AuthorizationProvider;

/**
 * Base entity class for Pages controller
 * @MappedSuperclass
 */
abstract class Entity extends Database\Entity implements AuthorizedEntityInterface
{
	const PERMISSION_NAME_EDIT_PAGE = 'edit_page';
	const PERMISSION_MASK_EDIT_PAGE = 256;

	const PERMISSION_NAME_SUPERVISE_PAGE = 'supervise_page';
	const PERMISSION_MASK_SUPERVISE_PAGE = 512;

	const PAGE_DISCR = 'page';
	const GROUP_DISCR = 'group';
	const APPLICATION_DISCR = 'application';
	const TEMPLATE_DISCR = 'template';

	/**
	 * Constant for Doctrine discriminator, used to get entity type without entity manager
	 */
	const DISCRIMINATOR = null;

	/**
	 * @var string
	 */
	protected $revision = '';
	
	/**
	 * @var array
	 */
	private $_authorizationAncestorsCache = array();

	/**
	 * Creates log writer instance
	 */
	protected function log()
	{
		return ObjectRepository::getLogger($this);
	}

	/**
	 * Set the property value. Return true on success, false on equal parameter,
	 * exception when argument not valid or different value was already set
	 * @param mixed $property
	 * @param mixed $value
	 * @return bool
	 * @throws Exception when trying to rewrite the property
	 * 	or invalid argument is passed
	 */
	protected function writeOnce(&$property, $value)
	{
		$sourceEntity = get_class($this);
		if (empty($value)) {
			$this->unlockAll();
			throw new Exception\RuntimeException("Second argument sent to method
					$sourceEntity::writeOnce() cannot be empty");
		}
		if ( ! is_object($value)) {
			$this->unlockAll();
			throw new Exception\RuntimeException("Second argument sent to method 
					$sourceEntity::writeOnce() must be an object");
		}
		if ($property === $value) {
			return false;
		}
		if ( ! empty($property)) {
			$this->unlockAll();
			$targetEntity = get_class($value);
			throw new Exception\RuntimeException("The property $targetEntity is write-once,
					cannot rewrite with different value for $sourceEntity");
		}
		$property = $value;

		return true;
	}

	/**
	 * Check if discriminators match for objects.
	 * They must be equal, with exceptions:
	 * 		* PageLocalization object can have Page block properties assigned to template block object
	 * 		* Application objects can be bound to Page objects except case with AbstractPage <-> Localization reference
	 * @param Entity $object
	 */
	public function matchDiscriminator(Entity $object)
	{
		if ( ! $object instanceof Entity) {
			throw new Exception\LogicException("Entity not passed to the matchDiscriminator method");
		}

		$discrA = $this::DISCRIMINATOR;
		$discrB = $object::DISCRIMINATOR;

		$this->log()->debug("Checking discr matching for $this and $object: $discrA and $discrB");

		if ($discrA == $discrB) {
			return;
		}

		// Allow binding page elements to application elements (except AbstractPage <-> Localization)
		if ($discrA != self::TEMPLATE_DISCR && $discrB != self::TEMPLATE_DISCR) {
			if ( ! ($this instanceof AbstractPage && $object instanceof Localization)) {
				return;
			}
		}

		/*
		 * Allow template elements being bound to the page elements in case of
		 * block property set to page localization and template block
		 */
		if ($this instanceof \Supra\Controller\Pages\Entity\PageLocalization && $object instanceof \Supra\Controller\Pages\Entity\TemplateBlock) {
			return;
		}

		$this->unlockAll();

		throw new Exception\RuntimeException("The object discriminators do not match for {$this} and {$object}");
	}

	/**
	 *
	 * @param AbstractUser $user
	 * @param Permission $permission
	 * @return boolean
	 */
	public function authorize(AbstractUser $user, $permission, $grant)
	{
		return $grant;
	}

	/**
	 * @return string
	 */
	public function getAuthorizationId()
	{
		return $this->getId();
	}

	/**
	 * @return string
	 */
	public function getAuthorizationClass()
	{
		return __CLASS__;
	}

	/**
	 * @return array
	 */
	public function getAuthorizationAncestors()
	{
		if (empty($this->_authorizationAncestorsCache)) {
			$this->_authorizationAncestorsCache = $this->getAuthizationAncestorsDirect();
		}

		return $this->_authorizationAncestorsCache;
	}

	protected function getAuthizationAncestorsDirect()
	{
		return $this->getAncestors(0, false);
	}

	/**
	 * @param AuthorizationProvider $ap 
	 */
	public static function registerPermissions(AuthorizationProvider $ap)
	{
		$ap->registerGenericEntityPermission(self::PERMISSION_NAME_EDIT_PAGE, self::PERMISSION_MASK_EDIT_PAGE, __CLASS__);
		$ap->registerGenericEntityPermission(self::PERMISSION_NAME_SUPERVISE_PAGE, self::PERMISSION_MASK_SUPERVISE_PAGE, __CLASS__);
	}

	/**
	 * @return string
	 */
	public static function getAlias()
	{
		return 'page';
	}

	/**
	 * @param string $revisionId 
	 */
	public function setRevisionId($revisionId)
	{
		$this->revision = $revisionId;
	}

	/**
	 * @return string
	 */
	public function getRevisionId()
	{
		return $this->revision;
	}

	/**
	 * 
	 */
	public function __clone()
	{
		if ( ! empty($this->id)) {
			$this->regenerateId();
			$this->setRevisionId(self::generateId(__CLASS__));
		}
	}

}
