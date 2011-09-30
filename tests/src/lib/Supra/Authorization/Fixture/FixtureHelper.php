<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\CmsApplicationConfiguration;

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
	}
	
	function build()
	{
		$this->em = ObjectRepository::getEntityManager($this->namespace);
		$this->up = ObjectRepository::getUserProvider($this->namespace);
		
		
		// User model drop/create
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);

		$metaDatas = $this->em->getMetadataFactory()->getAllMetadata();
		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
			return (strpos($classMetadata->namespace, 'Supra\User\Entity') === 0);
		};
		$metaDatas = \array_filter($metaDatas, $classFilter);
		
		$schemaTool->updateSchema($metaDatas);
		
		
		// ACL model drop/create
		try {
			$this->em->getConnection()->getWrappedConnection()->exec(file_get_contents(SUPRA_PATH . '/../database/authorization-mysql.sql'));
		} catch (\Exception $e) {}
		$this->ap = new AuthorizationProvider($this->em);
		
		
		// finding/adding "admin" user and granting all access to all apps
		$adminUserName = 'admin';
		
		$adminUser = $this->up->findUserByLogin($adminUserName);
		
		if( empty($adminUser)) {
			
			$adminUser = new \Supra\User\Entity\User();
			$this->em->persist($adminUser);

			$adminUser->setLogin($adminUserName);
			$adminUser->setName($adminUserName);
			$adminUser->setEmail($adminUserName);

			$this->up->getAuthAdapter()->credentialChange($adminUser, $adminUserName);
			$this->em->flush();		
		}
		
		foreach(CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			$this->ap->grantApplicationAllAccessPermission($adminUser, $appConfig);
		}
	}
}

