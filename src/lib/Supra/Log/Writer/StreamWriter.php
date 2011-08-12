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
	 * @return resource
	 */
	protected function getStream()
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
		$stream = $this->getStream();
		
		if (@fwrite($stream, $event->getMessage() . PHP_EOL) === false) {
			throw new Exception\RuntimeException('Cannot write log in the configured stream');
		}
	}
	
}