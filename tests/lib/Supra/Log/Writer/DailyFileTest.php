<?php

namespace Supra\Tests\Log\Writer;

use Supra\Tests\TestCase;
use Supra\Log\Writer\DailyFile;
use Supra\Log\Event;
use Supra\Log\Logger;
use Supra\Log\Formatter\Simple;

/**
 * Daily file log writer test
 */
class DailyFileTest extends TestCase
{
	/**
	 * Writer parameters
	 * @var array
	 */
	protected static $parameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => 'supra.test.%date%.log',
		'dateFormat' => 'd_m_Y'
	);

	/**
	 * Get log file name
	 * @return string
	 */
	public function getFileName()
	{
		$file = self::$parameters['folder'] . self::$parameters['file'];
		$date = date(self::$parameters['dateFormat']);
		return str_replace('%date%', $date, $file);
	}

	public function fileFound($logFile)
	{
		return file_exists($logFile);
	}

	/**
	 * Removes test log files
	 */
	public function removeTestLogFile()
	{
		$logFile = $this->getFileName();
		if (self::fileFound($logFile)) {
			$result = unlink($logFile);
			if ( ! $result) {
				self::fail('Could not remove test daily log file');
			}
		}
	}

	/**
	 * Set up
	 */
	public function setUp()
	{
		$this->removeTestLogFile();
	}

	/**
	 * Tear down
	 */
	public function tearDown()
	{
		$this->removeTestLogFile();
	}

	/**
	 * Writer test
	 */
	function testWriting()
	{
		$writer = new DailyFile(self::$parameters);

		$formatter = new Simple(array(
			'format' => '[%time%] %level% %logger% - %file%(%line%): %message%',
			'timeFormat' => '\\D\\A\\T\\E',
		));
		$writer->setFormatter($formatter);

		$event = new Event(array('message'), Logger::DEBUG, 'fileName', 1, 'loggerName');
		$writer->write($event);

		self::assertFileExists($this->getFileName());

		$content = file_get_contents($this->getFileName());
		$content = trim($content);
		
		self::assertEquals('[DATE] DEBUG loggerName - fileName(1): message', $content);
	}
}