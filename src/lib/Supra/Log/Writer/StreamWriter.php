<?php

namespace Supra\Log\Writer;

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
				throw new Exception(__CLASS__ . ': no stream url provided');
			}
			$url = $this->parameters['url'];
			
			$url = $this->formatUrl($url);
			
			$this->stream = @fopen($url, 'a');
			if ($this->stream === false) {
				throw new Exception(__CLASS__ . ': cannot open stream');
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
	 * @param array $event
	 */
	protected function _write($event)
	{
		$stream = $this->getStream();
		if ( ! is_resource($stream) || @fwrite($stream, $event['message'] . PHP_EOL) === false) {
			throw new Exception(__CLASS__ . ': cannot write in the stream');
		}
	}
	
}