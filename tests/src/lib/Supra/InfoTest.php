<?php

namespace Supra\Tests;

use Supra\Info;

/**
 */
class InfoTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Info
	 */
	protected $object;

	/**
	 */
	protected function setUp()
	{
		$this->object = new Info;
	}

	public function testGetHostName()
	{
		$this->object->hostName = 'sitesupra.com';
		self::assertEquals('sitesupra.com', $this->object->getHostName(Info::NO_SCHEME));
		self::assertEquals('http://sitesupra.com', $this->object->getHostName(Info::WITH_SCHEME));
		
		$this->object->hostName = 'https://sitesupra.com';
		self::assertEquals('sitesupra.com', $this->object->getHostName(Info::NO_SCHEME));
		self::assertEquals('https://sitesupra.com', $this->object->getHostName(Info::WITH_SCHEME));
		
		$this->object->hostName = 'sitesupra.com/';
		self::assertEquals('sitesupra.com', $this->object->getHostName(Info::NO_SCHEME));
		self::assertEquals('http://sitesupra.com', $this->object->getHostName(Info::WITH_SCHEME));
		
		$this->object->hostName = 'https://sitesupra.com/';
		self::assertEquals('sitesupra.com', $this->object->getHostName(Info::NO_SCHEME));
		self::assertEquals('https://sitesupra.com', $this->object->getHostName(Info::WITH_SCHEME));
	}

}
