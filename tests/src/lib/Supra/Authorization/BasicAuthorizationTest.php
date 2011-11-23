<?php

namespace Supra\Tests\Authorization;

use Supra\Tests\ObjectRepository\Mockup\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Authorization\Permission\PermissionStatus;

use Supra\Cms\ApplicationConfiguration;

use Supra\NestedSet\ArrayRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy;
use Supra\Database\Entity as DatabaseEntity;

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

class BasicAuthorizationTest extends \PHPUnit_Framework_TestCase 
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
	
	function setUp()
	{
		ObjectRepository::restoreCurrentState();
		
		$this->em = ObjectRepository::getEntityManager($this);
		
		// ACL model creation
		try {
			$this->em->getConnection()->getWrappedConnection()->exec(file_get_contents(SUPRA_PATH . '/../database/authorization-mysql.sql'));
		} catch (\Exception $e) {}
		
		$this->up = ObjectRepository::getUserProvider($this);
		
		// Begin "both" (maybe) connections
		$this->em->beginTransaction();
		$this->up->getEntityManager()->beginTransaction();

		$this->ap = new AuthorizationProvider();
		
		// Make the provider fetch the correct db connection
		ObjectRepository::setEntityManager($this->ap, $this->em);
		ObjectRepository::setDefaultAuthorizationProvider($this->ap);
		
		$sessionHandler = new \Supra\Session\Handler\PhpSessionHandler();

		$sessionManager = new \Supra\Session\SessionManager($sessionHandler);
		ObjectRepository::setDefaultSessionManager($sessionManager);
	}
	
	function makeNewUser() 
	{
		$name = 'test_' . rand(0, 999999999);
		
		$user = new \Supra\User\Entity\User();
		$this->up->getEntityManager()->persist($user);

		$user->setName($name);
		$user->setEmail($name . '@' . $name . '.com');
		$plainPassword = 'Norris';
		$password = new \Supra\Authentication\AuthenticationPassword($plainPassword);
		
		$this->up->getAuthAdapter()
				->credentialChange($user, $password);

		$this->up->getEntityManager()->flush();		
		
		\Log::debug('Made test user: ' . $name);
		
		return $user;
	}
	
	function tearDown() 
	{
		$this->up->getEntityManager()->rollback();
	
		// Rollback second EM as well if connections differs, second rollback otherwise
		$this->em->rollback();
	}
	
	function __testApplicationGrantAccessPermission() 
	{
		$appConfig = new \Supra\Cms\ApplicationConfiguration();
		$appConfig->id = 'Supra\Tests\DummyApplication';
		$appConfig->title = 'Dummy';
		$appConfig->path = '/cms/dummy';
		$appConfig->icon = '/cms/lib/supra/img/apps/dummy';
		
		$appConfig->authorizationAccessPolicy = new DummyAuthorizationAccessPolicy();
		$appConfig->authorizationAccessPolicy->applicationNamespace = 'Supra\Tests\DummyApplication';
		$appConfig->authorizationAccessPolicy->configure();
		
		$appConfig->configure();
		
		$user1 = $this->makeNewUser();
		$user2 = $this->makeNewUser();
		$user3 = $this->makeNewUser();

		$this->ap->grantApplicationAllAccessPermission($user1, $appConfig);
		$this->ap->grantApplicationSomeAccessPermission($user3, $appConfig);
		
		//self::assertEquals($this->ap->isApplicationAllAccessGranted($user1, $appConfig), true);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user2, $appConfig), false);

		self::assertEquals($this->ap->isApplicationAllAccessGranted($user3, $appConfig), false);		
		self::assertEquals($this->ap->isApplicationSomeAccessGranted($user3, $appConfig), true);
		
		$this->ap->revokeApplicationAllAccessPermission($user1, $appConfig);
		$this->ap->grantApplicationSomeAccessPermission($user1, $appConfig);
		
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user1, $appConfig), false);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user2, $appConfig), false);
		self::assertEquals($this->ap->isApplicationAllAccessGranted($user3, $appConfig), false);
		
		self::assertEquals($this->ap->isApplicationSomeAccessGranted($user1, $appConfig), true);
	}
	
	function __testEntityAccessPermission() 
	{
		$user1 = $this->makeNewUser();
		
		$rep = new ArrayRepository();
		
		$nodeNames = array(
				'meat',
				'fruit',
				'yellow',
				'red',
				'cherry',
				'tomato',
				'banana',
				'pork',
				'beef',
				'fish',
				'shark',
				'tuna'
			);
			
		$nodes = array();
		foreach($nodeNames as $nodeName) {
			
			$nodes[$nodeName] = new DummyAuthorizedEntity($nodeName);
			$rep->add($nodes[$nodeName]);
		}
		
		$nodes['meat']->addChild($nodes['pork']);
		$nodes['meat']->addChild($nodes['beef']);
		$nodes['meat']->addChild($nodes['fish']);
		$nodes['fish']->addChild($nodes['shark']);
		$nodes['fish']->addChild($nodes['tuna']);
		
		$nodes['fruit']->addChild($nodes['yellow']);
		$nodes['fruit']->addChild($nodes['red']);
		
		$nodes['yellow']->addChild($nodes['banana']);
		
		$nodes['red']->addChild($nodes['cherry']);
		$nodes['red']->addChild($nodes['tomato']);
		
		DummyAuthorizedEntity::registerPermissions($this->ap);
		
		$this->ap->setPermsission($user1, $nodes['fruit'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME, PermissionStatus::ALLOW);
		
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['fruit'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['banana'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		
		$this->ap->setPermsission($user1, $nodes['fish'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME, PermissionStatus::ALLOW);

		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['tuna'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), true);
		self::assertEquals($this->ap->isPermissionGranted($user1, $nodes['meat'], DummyAuthorizedEntity::PERMISSION_EDIT_NAME), false);
	}
	
	/**
	 * This test checks if object identity key in ace entry table is correct. 
	 * This checks that our modifications to Symfony's AclProvider class - removal of 
	 * casts of id's to integers - are functioning propperly, and id's written to db have 
	 * correct lenght.
	 */
	function testPatch() 
	{
		$appConfig = new \Supra\Cms\ApplicationConfiguration();
		$appConfig->id = 'Supra\Tests\DummyApplication';
		$appConfig->title = 'Dummy';
		$appConfig->path = '/cms/dummy';
		$appConfig->icon = '/cms/lib/supra/img/apps/dummy';
		
		$appConfig->authorizationAccessPolicy = new DummyAuthorizationAccessPolicy();
		$appConfig->authorizationAccessPolicy->applicationNamespace = 'Supra\Tests\DummyApplication';
		$appConfig->authorizationAccessPolicy->configure();
		
		$appConfig->configure();

		$user1 = $this->makeNewUser();
		
		$appConfig->authorizationAccessPolicy->grantApplicationAllAccessPermission($user1);
		
		$connection = $this->em->getConnection();
		
		$res = $connection->query('SELECT * FROM ' . AuthorizationProvider::ACL_ENTRY_TABLE_NAME);
		$row = $res->fetch();
		
		// argument does not really matress, as we need this for lenght check.
		$dummyIdString = DatabaseEntity::generateId(__FUNCTION__); 
		
		self::assertEquals(strlen($row['object_identity_id']), strlen($dummyIdString));
	}
}
