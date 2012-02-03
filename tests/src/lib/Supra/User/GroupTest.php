<?php

namespace Supra\Tests\User;

use Supra\Tests\TestCase;
use Supra\User;
use Supra\User\Entity;
use Supra\User\Exception;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class GroupTest extends \PHPUnit_Extensions_OutputTestCase
{
	/**
	 * @var User\UserProvider
	 */
	private $userProvider;
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->em = ObjectRepository::getEntityManager($this->userProvider);
		
		self::assertEquals('test', $this->em->_mode);
		
		$this->em->getUnitOfWork()
				->clear();
	}
	
	protected function tearDown()
	{
		parent::tearDown();
		
		$this->em->clear();
	}
	
	private function cleanUp($delete = false)
	{
		
		$query = $this->em->createQuery("delete from " . Entity\User::CN());
		$query->execute();
		$query = $this->em->createQuery("delete from " . Entity\Group::CN());
		$query->execute();
		$query = $this->em->createQuery("delete from " . Entity\AbstractUser::CN());
		
		$query->execute();
	}

	public function testCreateGroup()
	{
		$this->cleanUp();

		$group = $this->userProvider
				->createGroup();

		$group->setName('Super Heroes');

		$this->userProvider
				->updateGroup($group);
		
	}

	public function testGetGroupUsers()
	{
		$this->cleanUp();

		$group = $this->userProvider
				->createGroup();

		$group->setName('group111');
		
		$this->userProvider
				->updateGroup($group);

		foreach (array('user1', 'user2', 'user444') as $userName) {

			$user = $this->userProvider
					->createUser();

			$user->setName($userName);
			$user->setLogin($userName);
			$user->setEmail($userName);
			$user->setGroup($group);
			
			$this->userProvider
					->updateUser($user);
		}

		$group2 = $this->userProvider->findGroupByName('group111');
		$group2Users = $this->userProvider->getAllUsersInGroup($group2);

		self::assertEquals(count($group2Users), 3);
	}

}