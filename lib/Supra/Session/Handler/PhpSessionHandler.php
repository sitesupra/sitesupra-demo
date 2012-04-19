<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;

class PhpSessionHandler extends HandlerAbstraction
{
	/**
	 * Session expiration time in seconds
	 */
	const SESSION_EXPIRATION_TIME = 0;
	
	/**
	 * Starts session.
	 */
	public function start() 
	{
		session_name($this->sessionName);
		session_set_cookie_params(self::SESSION_EXPIRATION_TIME);
		
		// Set session ID if set
		if ( ! empty($this->sessionId)) {
			session_id($this->sessionId);
		}
		
		if ( ! session_start()) {
			$this->status = self::SESSION_COULD_NOT_START;
			throw new Exception\CouldNotStartSession();
		}
		
		$this->sessionId = session_id();
		
		parent::start();
	}
	
	/**
	 * Closes session, writes to storage.
	 */
	public function close() 
	{
		parent::close();
		
		session_write_close();
	}
}
