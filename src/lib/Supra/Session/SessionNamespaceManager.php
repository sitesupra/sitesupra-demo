<?php

namespace Supra\Session;

class SessionNamespaceManager
{
	const DEFAULT_NAMESPACE_CLASS = 'Supra\Session\SessionNamespace';
	const DEFAULT_NAMESPACE_NAME = 'defaultNamespace';
	
	/**
	 * @var HandlerAbstraction
	 */
	private $handler;
	
	/**
	 * @var mixed
	 */
	private $sessionData;
	
	/**
	 * @param HandlerAbstraction $handler
	 */
	public function __construct($handler) 
	{
		$this->handler = $handler;
		
		$this->handler->start();
		
		$this->sessionData =& $this->handler->getSessionData();
	}
	
	/**
	 * Creates default session namespace.
	 * 
	 * @param string $sessionNamespaceClass
	 * @return SessionNamespace
	 */
	function getDefaultSessionNamespace($sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
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
	function getOrCreateSessionNamespace($name = self::DEFAULT_NAMESPACE_NAME, $sessionNamespaceClass = self::DEFAULT_NAMESPACE_CLASS) 
	{
		if(!isset($this->sessionData[$name])) {

			$sessionNamespace = new $sessionNamespaceClass($name);
			$this->registerSessionNamespace($sessionNamespace);
		}
		
		return $this->getSessionNamespace($name);
	}
					
	/**
	 * Maps name to a session (thus creating the magical namespace). Throws 
	 * SessionNamespaceAlreadyExists on duplicate by name.
	 * 
	 * @param string $name
	 * @param SessionNamespace $session 
	 */
	function registerSessionNamespace(SessionNamespace $sessionNamespace) 
	{
		$name = $sessionNamespace->getName();
		
		if( !isset($this->sessionData[$name]) ) {
			$this->sessionData[$name] = $sessionNamespace;
		}
		else {
			throw new Exception\SessionNamespaceAlreadyExists();
		}
	}
	 
	/**
	 * Returns session instance for given name.
	 * 
	 * @param string $name
	 * @return SessionNamespace
	 */
	function getSessionNamespace($name) 
	{
		if(!isset($this->sessionData[$name])) {
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
	function sessionNamespaceIsRegistered($name) 
	{
		return isset($this->sessionData[$name]);
	}	
	
	/**
	 * Closes all namespaces and session itself.
	 */
	function close() 
	{
		foreach($this->sessionData as $sessionNamespace) {
			
			if($sessionNamespace instanceof SessionNamespace) {
				$sessionNamespace->close();	
			}
		}
		$this->handler->close();
	}
	
	/**
	 * Clears all namespaces.
	 */
	function clear() 
	{
		foreach($this->sessionNamespaces as $sessionNamespace) {

			if($sessionNamespace instanceof SessionNamespace) {
				$sessionNamespace->clear();
			}
		}
	}
}
