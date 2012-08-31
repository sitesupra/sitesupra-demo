<?php

namespace Supra\Session;

use Supra\Loader\Loader;
use Supra\Authentication\AuthenticationSessionNamespace;

class SessionManager
{
	const DEFAULT_NAMESPACE_CLASS = 'Supra\Session\SessionNamespace';
	const DEFAULT_NAMESPACE_NAME = 'defaultNamespace';
	const DEFAULT_AUTHENTICATION_NAMESPACE_CLASS = 'Supra\Authentication\AuthenticationSessionNamespace';
	
	/**
	 * @var Handler\HandlerAbstraction
	 */
	private $handler;
	
	/**
	 * Session array
	 * @var mixed
	 */
	private $sessionData = array();
	
	/**
	 * @var string
	 */
	private $authenticationNamespaceClass = self::DEFAULT_AUTHENTICATION_NAMESPACE_CLASS;
	
	/**
	 * @param HandlerAbstraction $handler
	 */
	public function __construct(Handler\HandlerAbstraction $handler)
	{
		$this->setHandler($handler);
	}

	/**
	 * @return Handler\HandlerAbstraction
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * @param Handler\HandlerAbstraction $handler
	 */
	public function setHandler(Handler\HandlerAbstraction $handler)
	{
		$this->handler = $handler;
	}

	/**
	 * @return bolelan
	 */
	public function isStarted()
	{
		$status = $this->handler->getSessionStatus();
		$started = ($status === Handler\HandlerAbstraction::SESSION_STARTED);

		return $started;
	}
	
	/**
	 * Starts the session
	 */
	public function start()
	{
		$this->handler->start();
		$this->sessionData = &$this->handler->getSessionData();
	}
	
	/**
	 * Starts the session if not started
	 */
	public function startIfStopped()
	{
		$status = $this->handler->getSessionStatus();
		
		if ($status != Handler\HandlerAbstraction::SESSION_STARTED) {
			$this->start();
		}
	}
	
	/**
	 * Changes the session ID inside the handler and reassigns the session data
	 * @param string $sessionId
	 */
	public function changeSessionId($sessionId)
	{
		$started = $this->isStarted();

		if ($started) {
			$this->clear();
			$this->close();
		}

		$this->handler->setSessionId($sessionId);
	}
		
	/**
	 * Shortcut for loading session namespace by interface
	 * @param string $spaceClass
	 * @return SessionNamespace
	 */
	public function getSpace($spaceClass)
	{
		return $this->getSessionNamespace($spaceClass, $spaceClass);
	}
	
	/**
	 * Shortcut to get authentication namespace
	 * @return AuthenticationSessionNamespace
	 */
	public function getAuthenticationSpace()
	{
		return $this->getSpace($this->authenticationNamespaceClass);
	}
	
	/**
	 * Set authentication session namespace
	 * 
	 * @param string $className
	 */
	public function setAuthenticationNamespaceClass($namespaceClass)
	{
		$this->authenticationNamespaceClass = $namespaceClass;
	}
	
	/**
	 * Creates default session namespace.
	 * 
	 * @param string $sessionNamespaceClass
	 * @return SessionNamespace
	 */
	public function getDefaultSessionNamespace($sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
	{
		return $this->getSessionNamespace(self::DEFAULT_NAMESPACE_NAME, $sessionNamespaceClass);
	}
	
	/**
	 * Checks for namespace by name and creates one if not found.
	 * 
	 * @param string $name
	 * @param string $sessionNamespaceClass
	 * @return SessionNamespace
	 */
	public function getSessionNamespace($name = self::DEFAULT_NAMESPACE_NAME, $sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
	{
		$this->startIfStopped();
		
		if ( ! isset($this->sessionData[$name]) || ! $this->sessionData[$name] instanceof SessionNamespace) {
			
			$sessionNamespace = Loader::getClassInstance($sessionNamespaceClass, 'Supra\Session\SessionNamespace');
			/* @var $sessionNamespace SessionNamespace */
			
			$sessionNamespace->setName($name);
			$this->registerSessionNamespace($sessionNamespace);
		}
		
		return $this->getExistingSessionNamespace($name);
	}
					
	/**
	 * Maps name to a session (thus creating the magical namespace). Throws 
	 * SessionNamespaceAlreadyExists on duplicate by name.
	 * 
	 * @param SessionNamespace $session 
	 */
	public function registerSessionNamespace(SessionNamespace $sessionNamespace) 
	{
		$this->startIfStopped();
		
		$name = $sessionNamespace->getName();
		$this->sessionData[$name] = $sessionNamespace;
	}
	 
	/**
	 * Returns session instance for given name.
	 * 
	 * @param string $name
	 * @return SessionNamespace
	 */
	protected function getExistingSessionNamespace($name) 
	{
		$this->startIfStopped();
		
		if ( ! isset($this->sessionData[$name])) {
			throw new Exception\SessionNamespaceNotFound();
		}
		
		return $this->sessionData[$name];
	}
	
	/**
	 * Checks if session namespace is registered.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function sessionNamespaceIsRegistered($name) 
	{
		$this->startIfStopped();
		
		return isset($this->sessionData[$name]);
	}	
	
	/**
	 * Closes all namespaces and session itself.
	 */
	public function close() 
	{
		if ( ! $this->isStarted()) {
			return;
		}

		foreach ($this->sessionData as $sessionNamespace) {
			
			if ($sessionNamespace instanceof SessionNamespace) {
				$sessionNamespace->close();	
			}
		}
		$this->handler->close();
	}
	
	/**
	 * Clears all namespaces.
	 */
	public function clear() 
	{
		$this->startIfStopped();
		
		foreach ($this->sessionData as $sessionNamespace) {

			if ($sessionNamespace instanceof SessionNamespace) {
				$sessionNamespace->clear();
			}
		}
	}
	
	public function setExpirationTime($time)
	{
		$this->handler->setExpirationTime($time);
	}
}
