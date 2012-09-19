<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;

class PhpSessionHandler extends HandlerAbstraction
{
	/**
	 * The name of PHP session opened
	 * @var string
	 */
	private static $phpSessionOpened = null;

	/**
	 * Marks the failure of the handler
	 * @var boolean
	 */
	private $failure = false;

	/**
	 * Session expiration time in seconds
	 */
	const SESSION_EXPIRATION_TIME = 0;

	/**
	 * Opens PHP session
	 * @throws Exception\CouldNotStartSession
	 */
	private function startPhpSession()
	{
		if (self::$phpSessionOpened === null) {

			if (empty($this->sessionName)) {
				$this->sessionName = session_name();
			} else {
				session_name($this->sessionName);
			}
			
			session_set_cookie_params(self::SESSION_EXPIRATION_TIME);

			$success = false;

			if ( ! empty($this->sessionId)) {
				session_id($this->sessionId);
				$success = session_start();
			} else {
				$success = session_start();
				$this->sessionId = session_id();
			}

			if ( ! $success) {
				$this->sessionStatus = self::SESSION_COULD_NOT_START;
				throw new Exception\CouldNotStartSession();
			}
		}

		self::$phpSessionOpened = $this->sessionName;
	}

	protected function findSessionId()
	{
//		//TODO: fixme
//		return @$_COOKIE[$this->sessionName] ?: md5(uniqid(true));
//
//		session_write_close();
//		session_name($this->sessionName);
//		session_start();
//		$sessionId = session_id();

		$this->startPhpSession();

		return $this->sessionId;
	}

	protected function readSessionData()
	{
//		session_write_close();
//		session_name($this->sessionName);
//		session_id($this->sessionId);
//		session_start();

//		$mustStart = false;
//
//		if (self::$phpSessionOpened) {
//			$currentSessionId = session_id();
//			$currentSessionName = session_name();
//
//			if ($currentSessionId !== $this->sessionId || $currentSessionName !== $this->sessionName) {
//				session_write_close();
//				$mustStart = true;
//			}
//		} else {
//			$mustStart = true;
//		}
//
//		if ($mustStart) {
//			session_id($this->sessionId);
//			session_name($this->sessionName);
//			session_start();
//		}

		$this->startPhpSession();

		if ($this->failure) {
			return array();
		}

		// Ignore the session data if session name doesn't match
		if (empty($_SESSION['SESSION_NAME']) || $_SESSION['SESSION_NAME'] != $this->sessionName) {
			$_SESSION = array(
				'SESSION_NAME' => $this->sessionName
			);
		}
		
		$sessionData = $_SESSION;

		return $sessionData;
	}

	public function start()
	{
		if (self::$phpSessionOpened !== null) {

			$this->failure = true;
			$this->sessionStatus = self::SESSION_COULD_NOT_START;
			throw new Exception\CouldNotStartSession("Session '" . self::$phpSessionOpened . "' is opened already.");
		}

		parent::start();
	}

	/**
	 * Closes session, writes to storage.
	 */
	public function close() 
	{
		if ($this->sessionStatus !== self::SESSION_STARTED) {
			return;
		}

		parent::close();

//		// nothing to close
//		if (empty($this->sessionId)) {
//			return;
//		}
//
//		parent::close();
//
//		$currentSessionId = session_id();
//		$currentSessionName = session_name();
//		$reopen = false;
//
//		if ($currentSessionId !== $this->sessionId || $currentSessionName !== $this->sessionName) {
//
//			$reopen = true;
//
//			session_write_close();
//
//			session_id($this->sessionId);
//			session_name($this->sessionName);
//			session_start();
//		}
//
//		$_SESSION = $this->sessionData;
//		session_write_close();
//
//		if ($reopen) {
//
//			session_id($currentSessionId);
//			session_name($currentSessionName);
//			session_start();
//		} else {
//			self::$phpSessionOpened = null;
//		}

		$_SESSION = $this->sessionData;
		session_write_close();
		self::$phpSessionOpened = null;
	}

	public function destroy()
	{
		if ($this->sessionStatus !== self::SESSION_STARTED) {
			return;
		}
		
		session_destroy();
		parent::destroy();
	}

}
