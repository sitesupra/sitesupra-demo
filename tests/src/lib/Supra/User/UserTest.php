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

	private function cleanUp($delete = false)
	{
		$em = ObjectRepository::getEntityManager($this);
		$query = $em->createQuery("delete from Supra\User\Entity\Abstraction\User");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\User");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\Group");
		$query->execute();
	}

	public function testCreateUser()
	{
		$this->cleanUp();

		$user = new Entity\User();
		$em = ObjectRepository::getEntityManager($this);

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$user->setName('Chuck');
		$user->setSalt();
		$user->setPassword(sha1('Norris' . $user->getSalt()));
		$user->setEmail('chuck@chucknorris.com');
		$em->flush();
	}

	public function testModifyUser()
	{

		$em = ObjectRepository::getEntityManager($this);
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $em->getRepository('Supra\User\Entity\User');

		$user = $repo->findOneByName('Chuck');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->persist($user);

		$user->setEmail('awesomechuck@chucknorris.com');

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

		$user = $repo->findOneByName('Chuck');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->persist($user);

		$user->setEmail('awesomechuckchucknorris.com');

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

		$user = $repo->findOneByName('Chuck');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->remove($user);
		$em->flush();

		$result = $repo->findOneByName('Chuck');

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

		$user->setName('Chuck');
		$user->setSalt();
		$user->setPassword(sha1('Norris' . $user->getSalt()));
		$user->setEmail('chuck@chucknorris.com');

		$userProvider->validate($user);

		$em->flush();

		$user = new Entity\User();

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$user->setName('Chuck');
		$user->setSalt();
		$user->setPassword(sha1('Norris' . $user->getSalt()));
		$user->setEmail('chuck@chucknorris.com');

		try {
			$userProvider->validate($user);
		} catch (Exception\RuntimeException $exc) {
			return;
		}

		$this->fail('Test should catch Runtime exception because user with same email already exists.');
	}

}