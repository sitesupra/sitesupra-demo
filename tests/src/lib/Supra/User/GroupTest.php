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
class GroupTest extends \PHPUnit_Extensions_OutputTestCase
{

	const SALT = '2j*s@;0?0saASf1%^&1!';

	private function cleanUp($delete = false)
	{
		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();
		$query = $em->createQuery("delete from Supra\User\Entity\Group");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\User");
		$query->execute();
		$query = $em->createQuery("delete from Supra\User\Entity\Abstraction\User");
		$query->execute();
	}
	
	public function testCreateGroup()
	{
		$this->cleanUp();

		$group = new Entity\Group();
		$userProvider = User\UserProvider::getInstance();
		$em = $userProvider->getEntityManager();

		/* @var $em Doctrine\ORM\EntityManager */
		$em->persist($group);
		
		$timeNow = new \DateTime('now');
		
		$group->setName('Super Heroes');
		$group->setCreatedTime($timeNow);
		$group->setModifiedTime($timeNow);

		$em->flush();
	}	
}