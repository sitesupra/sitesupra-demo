<?php

namespace Supra\Tests\User;

use Supra\Tests\TestCase;
use Supra\User;
use Supra\User\Entity;
use Supra\User\Exception;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class UserTest extends \PHPUnit_Extensions_OutputTestCase
{

	const SALT = '2j*s@;0?0saASf1%^&1!';

	private function cleanUp($delete = false)
	{
		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();
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
		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($user);

		$timeNow = new \DateTime('now');

		$user->setName('Chuck');
		$user->setPassword(md5('Norris' . self::SALT));
		$user->setEmail('chuck@chucknorris.com');
		$user->setCreatedTime($timeNow);
		$user->setModifiedTime($timeNow);

		$em->flush();
	}

	public function testModifyUser()
	{

		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $userProvider->getRepository();

		$user = $repo->findOneByName('Chuck');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->persist($user);

		$timeNow = new \DateTime('now');

		$user->setEmail('awesomechuck@chucknorris.com');
		$user->setModifiedTime($timeNow);

		$em->flush();

		$result = $repo->findOneByEmail('awesomechuck@chucknorris.com');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}
	}
	
	public function testModifyUserWithWrongEmail()
	{

		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $userProvider->getRepository();

		$user = $repo->findOneByName('Chuck');

		if (empty($user)) {
			$this->fail('Cant\'t find user with name: Chuck');
		}

		$em->persist($user);

		$timeNow = new \DateTime('now');

		$user->setEmail('awesomechuckchucknorris.com');
		$user->setModifiedTime($timeNow);
		
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
		$userProvider = User\UserProvider::getInstance();
		/* @var $em Doctrine\ORM\EntityManager */
		$em = $userProvider->getEntityManager();
		/* @var $repo Doctrine\ORM\EntityRepository */
		$repo = $userProvider->getRepository();

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

}