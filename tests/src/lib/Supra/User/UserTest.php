<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\User\Entity;
use Supra\User\Exception;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class UserTest extends \PHPUnit_Extensions_OutputTestCase
{
	
	const SALT = '2j*s@;0?0saASf1%^&1!';

	private static function getConnection()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}
	
	private function cleanUp($delete = false)
	{
		$query = self::getConnection()->createQuery("delete from Supra\User\Entity\User");
		$query->execute();
	}
	
	public function testCreateUser()
	{
		$this->cleanUp();
		
		$user = new Entity\User();
		self::getConnection()->persist($user);
		
		$timeNow = new \DateTime('now');
		
		$user->setUsername('Chuck');
		$user->setPassword(md5('Norris' . self::SALT));
		$user->setEmail('chuck@chucknorris.com');
		$user->setCreatedTime($timeNow);
		$user->setModifiedTime($timeNow);
		
		self::getConnection()->flush();
	}
	
}