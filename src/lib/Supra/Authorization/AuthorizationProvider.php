<?php

namespace Supra\Authorization;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Supra\User\Entity\Abstraction\User;
use Supra\ObjectRepository\ObjectRepository;

use Supra\NestedSet\Node\NodeInterface;

class AuthorizationProvider implements AuthorizationProviderInterface 
{
	/**
	 * @var MutableAclProvider;
	 */
	protected $aclProvider;
	
	/**
	 *
	 * @param EntityManager $entityManager
	 * @param array $options 
	 */
	function __construct(EntityManager $entityManager = null, $options = array()) 
	{
		if (empty($entityManager)) {
			$entityManager = ObjectRepository::getEntityManager(__NAMESPACE__);
		}
		
		$permissionGrantingStrategy = new PermissionGrantingStrategy();
		
		$this->aclProvider= new MutableAclProvider($entityManager->getConnection(), $permissionGrantingStrategy, $options);		
	}
	
	/**
	 * @param $object
	 * @return ObjectIdentity
	 */
	private function getObjectIdentity($object)
	{
		$objectIdentity = null;
		
		if ($object instanceof ObjectIdentity) {
			$objectIdentity = $object;
		}
		else if ($object instanceof AuthorizedEntityInterface) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), $object->getAuthorizationClass());
		}
		else if ($object instanceof AuthorizedControllerInterface) {
			$objectIdentity = new ObjectIdentity($object->getAuthorizationId(), $object->getAuthorizationClass());
		}
		else {
			throw new \RuntimeException('Do not know how to get object identity from ' . get_class($object));
		}
		
		return $objectIdentity;
	}
	
	/**
	 *
	 * @param User $user 
	 * @return UserSecurityIdentity
	 */
	private function getUserSecurityIdentity(User $user) 
	{
		return new UserSecurityIdentity($user->getName(), get_class($user));
	}
	
	/**
	 *
	 * @param User $user
	 * @param Object $object
	 * @param PermissionType $permissionType
	 * @param Integer $permission Shoud use constant from AuthorizationPermission class
	 */
	public function setPermsission(User $user, 
						$object, 
						PermissionType $permissionType, 
						$permission) 
	{
		$acl = $this->getObjectAcl($object);

		if (empty($acl)) {
			$objectIdentity = $this->getObjectIdentity($object);
			$acl = $this->aclProvider->createAcl($objectIdentity);
		}
		
		if ($acl instanceof Acl) {

			$userSecurityIdentity = $this->getUserSecurityIdentity($user);
			
			if ($permission == PermissionStatus::ALLOW) { 
				
				if( $this->getPermissionStatus($user, $object, $permissionType) == PermissionStatus::DENY ) {
					$acl->insertObjectAce($userSecurityIdentity, $permissionType->getMask());
				}
			}
			else if ($permission == PermissionStatus::DENY) {
				
				$aces = $acl->getObjectAces();
				
				foreach($aces as $index => $ace) {
					
					if ($ace->getMask() == $permissionType->getMask() && ($ace->getSecurityIdentity() == $userSecurityIdentity)) {
						$acl->deleteObjectAce($index);
						break;
					}
				}
			}
			else {
				
				throw new Exception\RuntimeException('Bad permission value! use constants from AuthorizationPermission class!');
			}
			
			$this->aclProvider->updateAcl($acl);
		}
		else {
			throw Exception\RuntimeException('Could not create ACL for this authorizationIdentity');
		}
	}

	/**
	 * @return integer
	 */
	public function getPermissionStatus(User $user, 
						$object, 
						PermissionType $permissionType)
	{
		$acl = $this->getObjectAcl($object);
		
		if (empty($acl)) {
			return PermissionStatus::DENY;
		}
		
		$aces = $acl->getObjectAces();
		
		$userSecurityIdentity = $this->getUserSecurityIdentity($user);
		
		foreach ($aces as $ace) {
			
			if ($ace instanceof \Symfony\Component\Security\Acl\Domain\Entry) {
				
				if ($permissionType->getMask() == $ace->getMask() && ($ace->getSecurityIdentity() == $userSecurityIdentity)) {
					return PermissionStatus::ALLOW;
				}
			}
		}
		
		return PermissionStatus::DENY;
	}
	
	/**
	 * @param mixed $object
	 * @return Acl
	 */
	private function getObjectAcl($object) 
	{
		$objectIdentity = $this->getObjectIdentity($object);
		$acl = null;
		
		try {
			$acl = $this->aclProvider->findAcl($objectIdentity);
		}
		catch (AclNotFoundException $e) { 
			// do nothing.
		}
		
		return $acl;
	}
	
	/**
	 *
	 * @param User $user
	 * @param ObjectIdentity $objectIdentity
	 * @param PermissionType $permissionType
	 * @return boolean
	 */
	function isPermissionGranted(User $user, 
						$object, 
						$permissionType)
	{
		if( ! $permissionType instanceof PermissionType  ) {
			$permissionType = $this->getPermissionTypeFromObject($object, $permissionType);
		}
		
		$acl = null;
		
		if ($object instanceof NodeInterface) {
			
			$ancestorsAndObject = $object->getAncestors(0, true);
			
			foreach ($ancestorsAndObject as $o) {
				
				$oid = $this->getObjectIdentity($o);
				
				try {
					
					$acl = $this->aclProvider->findAcl($oid);
					
					break;
				}
				catch(AclNotFoundException $e) {
					// do nothing, will try next oid
				}
			}
		}
		else {
			$acl = $this->getObjectAcl($object);
		}
		
		$userSecurityIdentity = $this->getUserSecurityIdentity($user);
		
		if ($acl instanceof Acl) {
			
			return (
						$acl->isGranted(array($permissionType->getMask()), array($userSecurityIdentity)) &&
						$object->authorize($user, $permissionType)
					);
		}
		else {
			return false;
		}
	}
	
	public function grantControllerAllAccessPermission(User $user, $controller)
	{
		$this->setPermsission($user, $controller, new ControllerSomeAccessPermission(), PermissionStatus::DENY);
		$this->setPermsission($user, $controller, new ControllerAllAccessPermission(), PermissionStatus::ALLOW);
	}
	
	public function revokeControllerAllAccessPermission(User $user, $controller)
	{
		$this->setPermsission($user, $controller, new ControllerAllAccessPermission(), PermissionStatus::DENY);
	}

	public function grantControllerSomeAccessPermission(User $user, $controller)
	{
		$this->setPermsission($user, $controller, new ControllerAllAccessPermission(), PermissionStatus::DENY);
		
		$this->setPermsission($user, $controller, new ControllerSomeAccessPermission(), PermissionStatus::ALLOW);
	}
	
	public function revokeControllerSomeAccessPermission(User $user, $controller)
	{
		$this->setPermsission($user, $controller, new ControllerSomeAccessPermission(), PermissionStatus::DENY);
	}

	public function isControllerAccessGranted(User $user, $controller)
	{
		\Log::debug('Checking access to ' . get_class($controller) . ' for user ' . $user->getName());
		
		if ($this->isControllerAllAccessGranted($user, $controller)) {
			return true;
		}
		else if ($this->isControllerSomeAccessGranted($user, $controller)) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function isControllerAllAccessGranted(User $user, $controller) 
	{
		$allAccessPermission = new ControllerAllAccessPermission();
		
		if ( 
				($this->getPermissionStatus($user, $controller, $allAccessPermission) == PermissionStatus::ALLOW) &&
				$controller->authorize($user, $allAccessPermission)
		) {
			return true;
		}
		else {
			return false;
		}
	}

	public function isControllerSomeAccessGranted(User $user, $controller) 
	{
		$someAccessPermission = new ControllerSomeAccessPermission();
		
		if ( 
				($this->getPermissionStatus($user, $controller, $someAccessPermission) == PermissionStatus::ALLOW) &&
				$controller->authorize($user, $someAccessPermission)
		) {
			return true;
		}
		else if ($this->getPermissionStatus($user, $controller, $someAccessPermission) == PermissionStatus::ALLOW) {
			return true;
		}
		else {
			return false;
		}
	}
	
	private function getPermissionTypes($object) 
	{
		if (
					$object instanceof AuthorizedEntityInterface || 
					$object instanceof AuthorizedControllerInterface
		) {
			return $object->getPermissionTypes();
		}
		else {
			throw new Exception\RuntimeException('Do not know how to get permission types from ' . get_class($object));
		}		
	}
	
	public function getDefinedPermissionStatuses(User $user, $object) 
	{
		$permissionTypes = $this->getPermissionTypes($object);
		
		$maskMap = array();
		foreach($permissionTypes as $permissionType) {
			$maskMap[$permissionType->getMask()] = $permissionType;
		}
 		
		$acl = $this->getObjectAcl($object);
		
		$result = array();		
		
		if ( ! empty($acl)) {
			
			$aces = $acl->getObjectAces();
			
			$userSecurityIdentity = $this->getUserSecurityIdentity($user);

			foreach ($aces as $ace) {
				
				if ($ace->getSecurityIdentity() == $userSecurityIdentity) {
					
					$permissionType = $maskMap[$ace->getMask];
					$result[$permissionType->getName()] = PermissionStatus::ALLOW;
				}
			}
		}
		
		return $result;
	}
	
	public function getEffectivePermissionStatuses(User $user, $object) 
	{
		$permissionTypes = $this->getPermissionTypes($object);
		
		$result = array();
		
		foreach($permissionTypes as $permissionTypeName => $permissionType) {
			$result[$permissionTypeName] = $this->isPermissionGranted($user, $object, $permissionType);
		}
		
		return $result;
	}
	
	public function getPermissionTypeFromObject($object, $permissionTypeName) {
		
		$permissionTypes = $this->getPermissionTypes($object);
		
		if( ! empty($permissionTypes[$permissionTypeName])) {
			return $permissionTypes[$permissionTypeName];
		}
		else {
			throw new \RuntimeException('Object does not have permission named ' . $permissionTypeName);
		}
	}
}
