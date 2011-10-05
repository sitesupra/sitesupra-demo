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
class UserTest extends \PHPUnit_Extensions_OutputTestCase
{
	const TEST_USER_NAME = 'Chuck123';
	
	/**
	 * @var User\UserProvider
	 */
	private $userProvider;
	
	/**
	 * @var \Supra\Authentication\AuthenticationPassword 
	 */
	private $password;
	
	protected function setUp()
	{
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$plainPassword = 'Norris';
		$this->password = new \Supra\Authentication\AuthenticationPassword($plainPassword);
	}
	
	private function cleanUp($delete = false)
	{
		$em = ObjectRepository::getEntityManager($this);
		
		// Removes test users
		$query = $em->createQuery("delete from Supra\User\Entity\User u where u.name = ?0");
		$query->execute(array(self::TEST_USER_NAME));
//		$query = $em->createQuery("delete from Supra\User\Entity\Group");
//		$query->execute();
	}

	public function testCreateUser()
	{
		$this->cleanUp();

		$user = new Entity\User();
		$em = ObjectRepository::getEntityManager($this);

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail('chuck@chucknorris.com');
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);
		
		$em->flush();
	}

	public function testModifyUser()
	{

		$em = ObjectRepository::getEntityManager($this);
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$user->setEmail('awesomechuck@chucknorris.com');
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user);

		$em->flush();

		$result = $repo->findOneByEmail('awesomechuck@chucknorris.com');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}
	}

	public function testModifyUserWithWrongEmail()
	{

		$em = ObjectRepository::getEntityManager($this);
		$userProvider = ObjectRepository::getUserProvider($this);
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->persist($user);

		$user->setEmail('awesomechuckchucknorris.com');

		$this->userProvider->getAuthAdapter()
				->credentialChange($user);
		
		try {
			$userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			return;
		}
		
		$em->flush();

		$this->fail('Succeed to change email');
	}

	public function testDeleteUser()
	{

		/* @var $em Doctrine\ORM\EntityManager */
		$em = ObjectRepository::getEntityManager($this);
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName(self::TEST_USER_NAME);

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->remove($user);
		$em->flush();

		$result = $repo->findOneByName(self::TEST_USER_NAME);

		if ( ! empty($result)) {
			$this->fail('Chuck should not exist in database. Nobody can add records on Chuck');
		}
	}

	public function testAddUserWithExistentEmail()
	{
		$this->cleanUp();

		$user = new Entity\User();
		$em = ObjectRepository::getEntityManager($this);
		$userProvider = ObjectRepository::getUserProvider($this);

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail('chuck@chucknorris.com');
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);

		$userProvider->validate($user);

		$em->flush();

		$user = new Entity\User();

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$user->setName(self::TEST_USER_NAME);
		$user->setEmail('chuck@chucknorris.com');
		
		$this->userProvider->getAuthAdapter()
				->credentialChange($user, $this->password);

		try {
			$userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			return;
		}

		$this->fail('Test should catch Runtime exception because user with same email already exists.');
	}
	
	public function testSaltReset()
	{
		$user = new Entity\User();
		$salt1 = $user->getSalt();
		
		self::assertNotEmpty($salt1);
		
		$salt2 = $user->resetSalt();
		
		self::assertNotEmpty($salt2);
		
		self::assertNotEquals($salt2, $salt1);
	}

}