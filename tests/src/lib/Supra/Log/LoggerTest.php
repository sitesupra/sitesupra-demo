<?php

namespace Supra\Tests\Log;

use Supra\Tests\TestCase,
		Supra\Log\Log,
		Supra\Log\Writer;

/**
 * Supra Logger test class
 */
class LoggerTest extends TestCase
{
	/**
	 * Initial timezone
	 * @var string
	 */
	protected $initialTimezone;

	/**
	 * Set up
	 */
	public function setUp()
	{
		$this->initialTimezone = date_default_timezone_get();
		Log::setDefaultTimezone($this->initialTimezone);
	}

	/**
	 * Tear down
	 */
	public function tearDown()
	{
		date_default_timezone_set($this->initialTimezone);
	}

	/**
	 * Get log instance test
	 */
	public function testGetInstance()
	{
		$instance1 = Log::getInstance();
		$instance2 = Log::getInstance();
		self::isInstanceOf('Supra\Log\Log')->evaluate($instance1);
		self::isInstanceOf('Supra\Log\Log')->evaluate($instance2);
		self::assertEquals($instance1, $instance2);
	}

	/**
	 * Test log level priorities
	 * @dataProvider levelTestProvider
	 * @param string $levelA
	 * @param string $levelB
	 */
	public function testLevels($levelA, $levelB)
	{
		self::assertTrue(Log::$levels[$levelA] < Log::$levels[$levelB]);
	}

	/**
	 * Provider for level test
	 * @return array
	 */
	public function levelTestProvider()
	{
		return array(
			array(Log::DEBUG, Log::INFO),
			array(Log::INFO, Log::WARN),
			array(Log::WARN, Log::ERROR),
			array(Log::ERROR, Log::FATAL)
		);
	}

	/**
	 * Test default timezone setting
	 * @dataProvider timezoneProvider
	 * @param string $timezoneA
	 * @param string $timezoneB
	 */
	public function testDefaultTimezone($timezone)
	{
		$time = time();
		$format = 'Y-m-d H:i:s';
		$timeStringA = Log::getDateInDefaultTimezone($format, $time);
		date_default_timezone_set($timezone);
		$timeStringB = Log::getDateInDefaultTimezone($format, $time);
		self::assertEquals($timeStringA, $timeStringB);
	}

	/**
	 * Timezone provider
	 * @return array
	 */
	public function timezoneProvider()
	{
		return array(
			array('Europe/Riga'),
			array('Australia/Sydney')
		);
	}

	public function testWriter()
	{
		$writerA = new Writer\Mock();
		$writerB = new Writer\Mock();
		Log::getInstance()->addWriter('test', $writerA);
	}
	
}