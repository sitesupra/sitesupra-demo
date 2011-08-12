<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;
use Supra\Log\Exception;

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
		'port' => null,
		'timeout' => 0.1,
	);
	
	/**
	 * Close the socket on destruct
	 */
	function __destruct()
	{
		if ( ! is_null($this->socket)) {
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
			
			if (empty($this->parameters['host'])) {
				throw Exception\RuntimeException::emptyConfiguration('socket host');
			}
			
			if (empty($this->parameters['port'])) {
				throw Exception\RuntimeException::emptyConfiguration('socket port');
			}
			
			$errno = null;
			$errstr = null;
			$this->socket = @fsockopen($this->parameters['host'], $this->parameters['port'], $errno, $errstr, $this->parameters['timeout']);
			
			if (empty($this->socket) || ! empty($errno)) {
				throw new Exception\RuntimeException("Cannot open socket {$this->parameters['host']}:{$this->parameters['port']} for logging, error [' . $errno . '] ' . $errstr");
			}
		}
		
		return $this->socket;
	}
	
	/**
	 * Write the message
	 * @param LogEvent $event
	 */
	protected function _write(LogEvent $event)
	{
		$socket = $this->getSocket();
		
		if (@fwrite($socket, $event->getMessage() . PHP_EOL) === false) {
			throw new Exception\RuntimeException("Cannot write log into the socket {$this->parameters['host']}:{$this->parameters['port']}");
		}
	}
}
