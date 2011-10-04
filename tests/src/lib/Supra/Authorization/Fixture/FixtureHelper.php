<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\CmsApplicationConfiguration;

use Supra\User\Entity\Abstraction\User;
use Supra\User\Entity\User as RealUser;
use Supra\User\Entity\Group as RealGroup;

class FixtureHelper 
{
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em; 
	
	/**
	 * @var AuthorizationProvider
	 */
	private $ap;
	
	/**
	 * @var \Supra\User\UserProvider;
	 */
	private $up;
	
	/**
	 * Namespace argument which is used for ObjectRepository calls
	 * @var mixed
	 */
	public $namespace;
	
	function __construct($namespace) 
	{
		$this->namespace = $namespace;
		
		$this->em = ObjectRepository::getEntityManager($this->namespace);
		$this->up = ObjectRepository::getUserProvider($this->namespace);
		
		// ACL model drop/create
		try {
			$this->em->getConnection()->getWrappedConnection()->exec(file_get_contents(SUPRA_PATH . '/../database/authorization-mysql.sql'));
		} catch (\Exception $e) {}
		
		$this->ap = new AuthorizationProvider($this->em);
	}
	
	function makeUser($userName, RealGroup $group = null) 
	{
		/* @var $user RealUser */
		$user = $this->up->findUserByLogin($userName);
		
		$em = $this->up->getEntityManager();
		
		if( empty($user)) {
			
			$user = new RealUser();
			$em->persist($user);

			$user->setLogin($userName);
			$user->setName($userName);
			$user->setEmail($userName);

			$this->up->getAuthAdapter()->credentialChange($user, $userName);
			$em->flush();		
		}
		
		if ( ! empty($group)) {
			
			$user->setGroup($group);
			$em->flush();
		}
		
		return $user;
	}
	
	
	function makeGroup($groupName) 
	{
		$group = $this->up->findGroupByName($groupName);
		$em = $this->up->getEntityManager();
		
		if( empty($group)) {
			
			$group = new \Supra\User\Entity\Group();
			$em->persist($group);
			$group->setName($groupName);
			$em->flush();		
		}
		
		return $group;
	}
	
	function build()
	{
		$adminUserName = 'admin';
		$superUserName = 'super';
		$contribUserName = 'contrib';

		$groups = array();
		$users = array();
		
		foreach(array($adminUserName, $superUserName, $contribUserName) as $userName) {
			
			$groupName = $userName . 's';
			
			$group = $this->makeGroup($groupName);
			$user = $this->makeUser($userName, $group);
			
			$groups[$groupName] = $group;
			$users[$userName] = $user;
		}

		$adminUser = $users[$adminUserName];
		foreach(CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			/* @var $appConfig \Supra\Cms\ApplicationConfiguration */
			
			$this->ap->grantApplicationAllAccessPermission($adminUser, $appConfig);
		}

		\Log::debug('==============================');
		
		$superUser = $users[$superUserName];
		foreach(CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			/* @var $appConfig \Supra\Cms\ApplicationConfiguration */
			
			if($appConfig->id != 'internal-user-manager') {
				$this->ap->grantApplicationAllAccessPermission($superUser, $appConfig);
			}
		}
		
		\Log::debug('==============================');
		
		$contribUser = $users[$contribUserName];
		foreach(CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			/* @var $appConfig \Supra\Cms\ApplicationConfiguration */
			
			if( ! in_array($appConfig->id, array('internal-user-manager', 'banner-manager'))) {
				$this->ap->grantApplicationAllAccessPermission($contribUser->getGroup(), $appConfig);
			}
		}	
		
		\Log::debug('==============================');
	}
}

