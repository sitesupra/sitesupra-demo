<?php

namespace Supra\Session;

use Supra\Loader\Loader;
use Supra\Authentication\AuthenticationSessionNamespace;

class SessionManager
{
	const DEFAULT_NAMESPACE_CLASS = 'Supra\Session\SessionNamespace';
	const DEFAULT_NAMESPACE_NAME = 'defaultNamespace';
	
	/**
	 * @var HandlerAbstraction
	 */
	private $handler;
	
	/**
	 * Session array
	 * @var mixed
	 */
	private $sessionData = array();
	
	/**
	 * @param HandlerAbstraction $handler
	 */
	public function __construct(Handler\HandlerAbstraction $handler)
	{
		$this->setHandler($handler);
		$this->start();
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
	 * Starts the session
	 */
	public function start()
	{
		$this->handler->start();
		$this->sessionData = &$this->handler->getSessionData();
	}
	
	/**
	 * Changes the session ID inside the handler and reassigns the session data
	 * @param string $sessionId
	 */
	public function changeSessionId($sessionId)
	{
		$this->clear();
		$this->close();
		$this->handler->setSessionId($sessionId);
		$this->start();
	}
		
	/**
	 * Shortcut for loading session namespace by interface
	 * @param string $spaceClass
	 * @return SessionNamespace
	 */
	public function getSpace($spaceClass)
	{
		return $this->getOrCreateSessionNamespace($spaceClass, $spaceClass);
	}
	
	/**
	 * Shortcut to get authentication namespace
	 * @return AuthenticationSessionNamespace
	 */
	public function getAuthenticationSpace()
	{
		return $this->getSpace('Supra\Authentication\AuthenticationSessionNamespace');
	}
	
	/**
	 * Creates default session namespace.
	 * 
	 * @param string $sessionNamespaceClass
	 * @return SessionNamespace
	 */
	public function getDefaultSessionNamespace($sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
	{
		return $this->getOrCreateSessionNamespace(self::DEFAULT_NAMESPACE_NAME, $sessionNamespaceClass);
	}
	
	/**
	 * Checks for namespace by name and creates one if not found.
	 * 
	 * @param string $name
	 * @param string $sessionNamespaceClass
	 * @return SessionNamespace
	 */
	public function getOrCreateSessionNamespace($name = self::DEFAULT_NAMESPACE_NAME, $sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
	{
		if ( ! isset($this->sessionData[$name]) || ! $this->sessionData[$name] instanceof SessionNamespace) {
			
			$sessionNamespace = Loader::getClassInstance($sessionNamespaceClass, 'Supra\Session\SessionNamespace');
			/* @var $sessionNamespace SessionNamespace */
			
			$sessionNamespace->setName($name);
			$this->registerSessionNamespace($sessionNamespace);
		}
		
		return $this->getSessionNamespace($name);
	}
					
	/**
	 * Maps name to a session (thus creating the magical namespace). Throws 
	 * SessionNamespaceAlreadyExists on duplicate by name.
	 * 
	 * @param SessionNamespace $session 
	 */
	public function registerSessionNamespace(SessionNamespace $sessionNamespace) 
	{
		$name = $sessionNamespace->getName();
		$this->sessionData[$name] = $sessionNamespace;
	}
	 
	/**
	 * Returns session instance for given name.
	 * 
	 * @param string $name
	 * @return SessionNamespace
	 */
	public function getSessionNamespace($name) 
	{
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
		return isset($this->sessionData[$name]);
	}	
	
	/**
	 * Closes all namespaces and session itself.
	 */
	public function close() 
	{
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
		foreach ($this->sessionData as $sessionNamespace) {

			if ($sessionNamespace instanceof SessionNamespace) {
				$sessionNamespace->clear();
			}
		}
	}
}
