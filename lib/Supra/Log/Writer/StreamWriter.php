<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;
use Supra\Log\Exception;

/**
 * Stream log writer
 */
class StreamWriter extends WriterAbstraction
{

	/**
	 * Stream resource
	 * @var resource
	 */
	protected $stream;
	
	/**
	 * Turns on/off log highlighting in console
	 * @var boolean 
	 */
	protected $coloredLogs = false;

	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'url' => null
	);

	/**
	 * Close the stream on destruct
	 */
	function __destruct()
	{
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
	}

	/**
	 * Clear the resource variable on unserialize
	 */
	function __wakeup()
	{
		$this->stream = null;
		parent::__wakeup();
	}

	/**
	 * Get stream resource
	 * @param LogEvent $event used if stream selction differs from event parameters
	 * @return resource
	 */
	protected function getStream(LogEvent $event)
	{
		if (is_null($this->stream)) {

			if (empty($this->parameters['url'])) {
				throw Exception\RuntimeException::emptyConfiguration('stream url');
			}
			$url = $this->parameters['url'];

			$url = $this->formatUrl($url);

			$this->stream = @fopen($url, 'a');
			if ($this->stream === false) {
				throw new Exception\RuntimeException("Cannot open log writer stream {$url}");
			}
		}

		return $this->stream;
	}

	/**
	 * Formats URL
	 * @param string $url
	 * @return string
	 */
	protected function formatUrl($url)
	{
		return $url;
	}

	/**
	 * Write the message
	 * @param LogEvent $event
	 */
	protected function _write(LogEvent $event)
	{
		$stream = $this->getStream($event);
		$message = $event->getMessage();

		if ($this->coloredLogs) {

			$level = $event->getLevelPriority();

			$blankColor = "\033[00m";
			$redColor = "\033[00;31m";
			$yellowColor = "\033[00;33m";
			
			if (in_array($level, array(LogEvent::$levels[LogEvent::ERROR],
						LogEvent::$levels[LogEvent::FATAL]))) {
				$message = $redColor . $message . $blankColor;
			}

			if ($level == LogEvent::$levels[LogEvent::WARN]) {
				$message = $yellowColor . $message . $blankColor;
			}
		}
		
		if (@fwrite($stream, $message . PHP_EOL) === false) {
			throw new Exception\RuntimeException('Cannot write log in the configured stream');
		}
	}

}