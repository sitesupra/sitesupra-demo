<?php

namespace Supra\Tests\Validator\Type;

use Supra\Validator\Type\EmailType;

class EmailTypeTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var EmailType
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new EmailType;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		
	}

	public function testValidate()
	{
		$value1 = $value = 'x@gmail.com';
		$this->object->validate($value);
		self::assertSame($value1, $value);
		
		$value1 = $value = 'xxx_aaa+2@mail.eu';
		$this->object->validate($value);
		self::assertSame($value1, $value);
		
		$value1 = $value = 'aigars@gedroics.vig';
		$this->object->validate($value);
		self::assertSame($value1, $value);
	}
	
	/**
	 * @expectedException \Supra\Validator\Exception\ValidationFailure
	 */
	public function testInvalid()
	{
		$a = 'x';
		$this->object->validate($a);
	}

}
