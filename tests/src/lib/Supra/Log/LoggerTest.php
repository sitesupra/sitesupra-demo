<?php

namespace Supra\Tests\Log;

use Supra\Tests\TestCase;
use Supra\Log\Log;
use Supra\Log\Writer;
use Supra\Log\LogEvent;

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
		LogEvent::setDefaultTimezone($this->initialTimezone);
	}

	/**
	 * Tear down
	 */
	public function tearDown()
	{
		date_default_timezone_set($this->initialTimezone);
	}

	/**
	 * Test log level priorities
	 * @dataProvider levelTestProvider
	 * @param string $levelA
	 * @param string $levelB
	 */
	public function testLevels($levelA, $levelB)
	{
		self::assertTrue(LogEvent::$levels[$levelA] < LogEvent::$levels[$levelB]);
	}

	/**
	 * Provider for level test
	 * @return array
	 */
	public function levelTestProvider()
	{
		return array(
			array(LogEvent::DEBUG, LogEvent::INFO),
			array(LogEvent::INFO, LogEvent::WARN),
			array(LogEvent::WARN, LogEvent::ERROR),
			array(LogEvent::ERROR, LogEvent::FATAL)
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
		$timeStringA = LogEvent::getDateInDefaultTimezone($format, $time);
		date_default_timezone_set($timezone);
		$timeStringB = LogEvent::getDateInDefaultTimezone($format, $time);
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
}
