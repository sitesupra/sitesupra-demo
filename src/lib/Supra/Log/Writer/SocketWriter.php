<?php

namespace Supra\Log\Writer;

use Supra\Log\Log;

/**
 * Socket log writer
 */
class SocketWriter extends WriterAbstraction
{
	/**
	 * Socket resource
	 * @var resource
	 */
	protected $socket;
	
	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'host' => 'tcp://127.0.0.1',
		'port' => 4446,
		'timeout' => 0.1,
	);
	
	/**
	 * Close the socket on destruct
	 */
	function __destruct()
	{
		if (is_resource($this->socket)) {
			fclose($this->socket);
		}
	}
	
	/**
	 * Clear the resource variable on unserialize
	 */
	function __wakeup()
	{
		$this->socket = null;
		parent::__wakeup();
	}
	
	/**
	 * Get socket resource
	 * @return resource
	 */
	protected function getSocket()
	{
		if (is_null($this->socket)) {
			$this->socket = @fsockopen($this->parameters['host'], $this->parameters['port'], $errno, $errstr, $this->parameters['timeout']);
			if ($errno) {
				Log::swarn(__CLASS__ . ': cannot open socket, error [' . $errno . '] ' . $errstr);
			}
		}
		return $this->socket;
	}
	
	/**
	 * Write the message
	 * @param array $event
	 */
	protected function _write($event)
	{
		$socket = $this->getSocket();
		if (is_resource($socket)) {
			if (@fwrite($socket, $event['message'] . PHP_EOL) === false) {
				Log::swarn(__CLASS__ . ': cannot write in the socket');
			}
		}
	}
}