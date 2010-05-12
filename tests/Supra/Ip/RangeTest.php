<?php

namespace Supra\Tests\Ip;

use Supra\Tests\TestCase;
use Supra\Ip\Range;

/**
 * IP range test
 */
class RangeTest extends TestCase
{
	/**
	 * Test IP range object's includes() method
	 * @dataProvider rangeTestDataProvider
	 * @param string $rangeString
	 * @param string $ipAddress
	 * @param boolean $expectedResult
	 */
	public function testIncludes($rangeString, $ipAddress, $expectedResult)
	{
		$range = new Range($rangeString);
		$result = $range->includes($ipAddress);
		self::assertEquals($expectedResult, $result);
	}

	/**
	 * Range test data provider
	 * @return array
	 */
	public function rangeTestDataProvider()
	{
		return array(
			array('1.2.3.4', '1.2.3.4', true),
			array('1.2.3.4', '1.2.3.5', false),
			array('10.0.1.0/24,127.0.0.*', '127.0.0.9', true),
			array('10.0.1.0/24,127.0.0.*', '10.0.1.24', true),
			array('10.0.1.0/24,127.0.0.*', '192.168.0.1', false),
			array('0.0.0.0', '0.0.0.0', true),
			array('0.0.0.0/0', '255.255.255.255', true),
			array('0.0.0.0/1', '255.255.255.255', false),
			array('0.0.0.0/1', '127.255.255.255', true),
			array('0.0.0.0/8', '1.255.255.255', false),
		);
	}

	/**
	 * Test invalid values
	 * @dataProvider getInvalidRanges
	 * @expectedException Supra\Ip\Exception
	 */
	public function testStrictMode()
	{
		new Range(array('invalid argument'));
	}

	/**
	 * Invalid range string provider
	 * @return array
	 */
	public function getInvalidRanges()
	{
		return array(
			array(array('array')),
			array('invalid'),
			array('12.12.12'),
			array('255.255.255.256'),
		);
	}
}