<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;

class PhpSessionHandler extends HandlerAbstraction
{
	const SESSION_EXPIRATION_TIME = 0;

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
	 * Defines, weither sessionId cookie will be sent only via ssl connection
	 * @var boolean
	 */
	private $secureOnly = false;

	/**
	 * @var string
	 */
	private $cookieDomain;

	/**
	 * @var bool
	 */
	private $cookieHttpOnly = false;

	/**
	 * @throws Exception\CouldNotStartSession
	 */
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

	/**
	 * 
	 */
	public function destroy()
	{
		if ($this->sessionStatus !== self::SESSION_STARTED) {
			return;
		}

		self::$phpSessionOpened = null;

		session_destroy();
		parent::destroy();
	}
	
	/**
	 * @param boolean $cookieSecureOnly
	 */
	public function setCookieSecureOnly($cookieSecureOnly)
	{
		$this->cookieSecureOnly = $cookieSecureOnly;
	}

	/**
	 * @param string $cookieDomain
	 */
	public function setCookieDomain($cookieDomain)
	{
		$this->cookieDomain = $cookieDomain;
	}

	/**
	 * @param bool $cookieHttpOnly
	 */
	public function setCookieHttpOnly($cookieHttpOnly)
	{
		$this->cookieHttpOnly = $cookieHttpOnly;
	}

	/**
	 * @deprecated use setCookieSecureOnly() instead
	 * 
	 * @param bool $secureOnlySession
	 */
	public function setSecureOnlySession($secureOnlySession)
	{
		$this->setCookieSecureOnly($secureOnlySession);
	}

	/**
	 * Opens PHP session
	 * @throws Exception\CouldNotStartSession
	 */
	private function startPhpSession()
	{
		//FIXME: Working with global variables directly. Should have request object.
		if ($this->secureOnly && (empty($_SERVER['HTTPS']) || strcasecmp($_SERVER['HTTPS'], 'off') === 0)) {
			$this->sessionStatus = self::SESSION_COULD_NOT_START;
			throw new Exception\CouldNotStartSession("Session marked as secure");
		}

		if (self::$phpSessionOpened === null) {

			if (empty($this->sessionName)) {
				$this->sessionName = session_name();
			} else {
				session_name($this->sessionName);
			}

			session_set_cookie_params(
					self::SESSION_EXPIRATION_TIME,
					'/',
					$this->cookieDomain,
					$this->secureOnly,
					$this->cookieHttpOnly
			);

			$success = false;

			if (empty($this->sessionId)) {
				if (isset($_COOKIE[$this->sessionName])) {
					$this->sessionId = $_COOKIE[$this->sessionName];
				} else {

					session_start();
					$success = session_regenerate_id();
					$_SESSION = array();

					if ( ! $success) {
						$this->sessionStatus = self::SESSION_COULD_NOT_START;
						throw new Exception\CouldNotStartSession();
					}

					$this->sessionId = session_id();
				}
			}

			session_id($this->sessionId);

			if ( ! session_start()) {
				$this->sessionStatus = self::SESSION_COULD_NOT_START;
				throw new Exception\CouldNotStartSession();
			}
		}

		self::$phpSessionOpened = $this->sessionName;
	}

	/**
	 * @return string
	 */
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

	/**
	 * @return mixed
	 */
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
		if (empty($_SESSION['SESSION_NAME'])) {
			$_SESSION = array(
				'SESSION_NAME' => $this->sessionName
			);
		}

		if ($_SESSION['SESSION_NAME'] != $this->sessionName) {

			session_regenerate_id();
			$this->sessionId = session_id();

			$_SESSION = array(
				'SESSION_NAME' => $this->sessionName
			);
			//throw new \RuntimeException("This session not meant to be used by session with name " . $this->sessionName . ' ' . $_SESSION['SESSION_NAME'] . ' provided');
		}

		$sessionData = $_SESSION;

		return $sessionData;
	}
}
