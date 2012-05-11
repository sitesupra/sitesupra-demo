<?php

namespace Supra\Tests\Database\Upgrade;

use Supra\Upgrade\Database\SqlUpgradeFile;

class SqlUpgradeFileTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var SqlUpgradeFile
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new SqlUpgradeFile(__DIR__ . '/sample.sql');
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		
	}

	public function testGetAnnotations()
	{
		$annotations = $this->object->getAnnotations();
		self::assertEquals(array('one' => 'two', 'three' => '', 'five' => '', 'four' => 'SIX'), $annotations);
	}

}
