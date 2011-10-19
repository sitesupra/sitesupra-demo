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
		$this->em = $this->userProvider->getEntityManager();
		
		self::assertEquals('test', $this->em->_mode);
	}
	
	private function cleanUp($delete = false)
	{
		
		$query = $this->em->createQuery("delete from Supra\User\Entity\User");
		$query->execute();
		$query = $this->em->createQuery("delete from Supra\User\Entity\Group");
		$query->execute();
		$query = $this->em->createQuery("delete from Supra\User\Entity\Abstraction\User");

		$query->execute();
	}

	public function testCreateGroup()
	{
		$this->cleanUp();

		$group = new Entity\Group();

		$this->em->persist($group);

		$group->setName('Super Heroes');

		$this->em->flush();
	}

	public function testGetGroupUsers()
	{
		$this->cleanUp();

		$group = new Entity\Group();
		$this->em->persist($group);

		$group->setName('group111');
		$this->em->flush();

		foreach (array('user1', 'user2', 'user444') as $userName) {

			$user = new Entity\User();
			$this->em->persist($user);

			$user->setName($userName);
			$user->setLogin($userName);
			$user->setEmail($userName);
			$user->setGroup($group);
			$this->em->flush();
		}

		$group2 = $this->userProvider->findGroupByName('group111');
		$group2Users = $this->userProvider->getAllUsersInGroup($group2);

		self::assertEquals(count($group2Users), 3);
	}

}