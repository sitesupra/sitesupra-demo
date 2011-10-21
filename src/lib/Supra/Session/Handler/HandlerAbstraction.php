<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;

/**
 * Abstract session handler class
 */
abstract class HandlerAbstraction 
{
	const DEFAULT_SESSION_NAME = 'SID';
	
	const SESSION_NOT_STARTED = 1000;
	const SESSION_STARTED = 1001;
	const SESSION_CLOSED = 1002;
	const SESSION_COULD_NOT_START = 1003;
	const SESSION_COULD_NOT_CLOSE = 1004;	
	
	protected $persistOnClose = true;
	
	/**
	 * Session current status
	 * @var int
	 */
	protected $sessionStatus = self::SESSION_NOT_STARTED;
	
	/**
	 * Session ID
	 * @var string
	 */
	protected $sessionId;
	
	/**
	 * Session name
	 * @var string
	 */
	protected $sessionName;
	
	/**
	 * Session data
	 * @var array
	 */
	protected $sessionData;
	
	/**
	 * @param string $sessionName
	 */
	public function __construct($sessionName = self::DEFAULT_SESSION_NAME) 
	{
		$this->sessionName = $sessionName;
	}
	
	/**
	 * Starts session. On failure should set status to SESSION_COULD_NOT_START 
	 * and throw Exception/CouldNotStartSession.
	 */
	public function start() 
	{
		$this->sessionStatus = self::SESSION_STARTED;
		$this->sessionData = & $_SESSION;
	}
	
	/**
	 * Closes session. If persistOnClose is true, should write session data to 
	 * persistence. On failure should set status to SESSION_COULD_NOT_CLOSE and 
	 * throw Exception/CouldNotCloseSession.
	 */
	public function close() 
	{
		$this->sessionStatus = self::SESSION_CLOSED;
	}
	
	/**
	 * Clears all session data.
	 */
	public function clear() 
	{
		$this->sessionData = array();
	}
	
	/**
	 * @param boolean $persistOnClose 
	 */
	public function persistOnClose($persistOnClose) 
	{
		$this->persistOnClose = $persistOnClose;
	}
	
	/**
	 * Returns session status.
	 * @return integer
	 */
	public function getSessionStatus()
	{
		return $this->sessionStatus;
	}
	
	/**
	 * Returns session data AS A REFERENCE!!!
	 * @return mixed
	 */
	public function &getSessionData() 
	{
		if ($this->sessionStatus != self::SESSION_STARTED) {
			$this->start();
		}
		
		return $this->sessionData;
	}

	/**
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->sessionId;
	}

	/**
	 * @param string $sessionId
	 */
	public function setSessionId($sessionId)
	{
		if ($this->sessionStatus == self::SESSION_STARTED) {
			throw new Exception\SessionStarted("Cannot set session ID when session is started");
		}
		
		$this->sessionId = $sessionId;
	}
}
