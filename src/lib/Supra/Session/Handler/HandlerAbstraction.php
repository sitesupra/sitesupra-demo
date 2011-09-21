<?php

namespace Supra\Session\Handler;

abstract class HandlerAbstraction 
{
	const DEFAULT_SESSION_NAME = 'SID';
	
	const SESSION_NOT_STARTED = 1000;
	const SESSION_STARTED = 1001;
	const SESSION_CLOSED = 1002;
	const SESSION_COULD_NOT_START = 1003;
	const SESSION_COULD_NOT_CLOSE = 1004;	
	
	protected $persistOnClose;
	protected $sessionStatus;
	
	protected $sessionName;
	
	protected $sessionData;
	
	public function __construct($sessionName = self::DEFAULT_SESSION_NAME) 
	{
		$this->sessionName = $sessionName;
		
		$this->persistOnClose = true;
		$this->sessionStatus = self::SESSION_NOT_STARTED;
	}
	
	/**
	 * Starts session. On failure should set status to SESSION_COULD_NOT_START 
	 * and throw Exception/CouldNotStartSession.
	 */
	public function start() 
	{
		$this->sessionStatus = self::SESSION_STARTED;
		$this->sessionData =& $_SESSION;
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
	 * @param boolean $yes_or_no 
	 */
	public function persistOnClose($yes_or_no) 
	{
		$this->persistOnClose = $yes_or_no;
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
}
