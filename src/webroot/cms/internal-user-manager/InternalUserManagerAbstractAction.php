<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Cms\CmsAction;
use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\User\UserProvider;
use Doctrine\ORM\EntityManager;

/**
 * Internal user manager action controller
 * @method JsonResponse getResponse()
 */
class InternalUserManagerAbstractAction extends CmsAction
{
	/**
	 * @var UserProvider
	 */
	protected $userProvider;
	
	/**
	 * @var EntityManager
	 */
	protected $entityManager;
	
	/**
	 * @var array
	 */
	protected $dummyGroupMap;
	
	/**
	 * Bind objects
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->entityManager = ObjectRepository::getEntityManager($this->userProvider);
		
		$this->dummyGroupMap = array('admins' => 1, 'contribs' => 3, 'supers' => 2);
	}
	
	protected function getRequestedEntity($key, $className)
	{
		if ( ! $this->hasRequestParameter($key)) {
			throw new CmsException('internalusermanager.validation_error.user_id_not_provided');
		}
		
		$id = $this->getRequestParameter($key);
		$user = $this->entityManager->find($className, $id);
		
		if (is_null($user)) {
			throw new CmsException('internalusermanager.validation_error.user_not_exists');
		}
		
		return $user;
	}
	
	/**
	 * @return Entity\Abstraction\User
	 */
	protected function getEntity($key = 'id')
	{
		$user = $this->getRequestedEntity($key, 'Supra\User\Entity\Abstraction\User');
		
		return $user;
	}
	
	/**
	 * @return Entity\User
	 */
	protected function getUser($key = 'id')
	{
		$user = $this->getRequestedEntity($key, 'Supra\User\Entity\User');
		
		return $user;
	}
	
	/**
	 * @return Entity\Group
	 */
	protected function getGroup($key = 'id')
	{
		$group = $this->getRequestedEntity($key, 'Supra\User\Entity\Group');
		
		return $group;
	}
}