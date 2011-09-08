<?php

namespace Supra\Session\Handler;

class Internal extends HandlerAbstraction
{
	/**
	 * Session expiration time in seconds
	 */
	const SESSION_EXPIRATION_TIME = 300;
	
	/**
	 * Starts session.
	 */
	public function start() 
	{
		session_name($this->sessionName);
		session_set_cookie_params(self::SESSION_EXPIRATION_TIME);
		
		if( ! session_start()) {
			
			$this->status = self::SESSION_COULD_NOT_START;
			throw new Exception\CouldNotStartSession();
		}
		
		parent::start();
	}
	
	/**
	 * Closes session, writes to storage.
	 */
	public function close() 
	{
		session_write_close();
		
		parent::close();
	}
	
	/**
	 * Clears all session data.
	 */
	public function clear() {
		
		$this->sessionData = array();
		
		parent::clear();
	}
}
