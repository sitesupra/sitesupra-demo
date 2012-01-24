<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;

/**
 * Abstract session handler class
 */
abstract class HandlerAbstraction 
{
	const DEFAULT_SESSION_NAME = 'SID';
	const SESSION_LAST_ACTIVITY_OFFSET = '__lastActivity';
	
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
	 * Session expiration time in seconds
	 * @var integer
	 */
	protected $expirationTime;
	
	/**
	 * @var boolean
	 */
	protected $silentAccess = false;
	
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
		
		if ( ! isset($this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET])) {
			$this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET] = time();
		}
			
		$this->checkSessionExpire();
	}
	
	/**
	 * Closes session. If persistOnClose is true, should write session data to 
	 * persistence. On failure should set status to SESSION_COULD_NOT_CLOSE and 
	 * throw Exception/CouldNotCloseSession.
	 */
	public function close() 
	{
		$this->sessionStatus = self::SESSION_CLOSED;
		
		if ( ! $this->silentAccess) {
			$this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET] = time();
		}
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
	
	/**
	 * @param integer $time
	 */
	public function setExpirationTime($time) 
	{
		$this->expirationTime = $time;
	}
	
	/**
	 * Checks session for expiration, and if it is, drops stored session data
	 */
	public function checkSessionExpire()
	{

		$expireTime = $this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET] + $this->expirationTime;
		
		if ($expireTime < time()) {
			$this->clear();
			$this->sessionId = null;
		}
		
	}
	
	/**
	 * Marks session data access as silent, 
	 * so session last activity time wouldn't be updated
	 * @param type $silentAccess
	 */
	public function setSilentAccess($silentAccess = false)
	{
		$this->silentAccess = $silentAccess;
	}
}
