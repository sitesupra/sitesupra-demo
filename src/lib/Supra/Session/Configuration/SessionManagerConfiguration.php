<?php

namespace Supra\Session\Configuration;

use Supra\Session\SessionManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;

class SessionManagerConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
	
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var array
	 */
	public $namespaces = array();
	
	/**
	 * @var boolean
	 */
	public $isDefault;
	
	/**
	 * Session expiration time in seconds
	 * @var integer
	 */
	public $sessionExpirationTime;
	
	/**
	 * Adds PHP namespace (a string) to list of namespaces that will be registered in object repository for this session namespace.
	 * @param string $namespace 
	 */
	public function addNamespace($namespace) 
	{
		$this->namespaces[] = $namespace;
	}
	
	public function configure()
	{
		$handler = Loader::getClassInstance($this->handlerClass, 
				'Supra\Session\Handler\HandlerAbstraction');
		/* @var $handler \Supra\Session\Handler\HandlerAbstraction */

		if ( ! empty($this->name)) {
			$handler->setSessionName($this->name);
		}
		
		$sessionManager = new SessionManager($handler);
		$sessionManager->setExpirationTime($this->sessionExpirationTime);
		
		foreach ($this->namespaces as $namespace) {
			ObjectRepository::setSessionManager($namespace, $sessionManager);
		}
		
		if ($this->isDefault) {
			ObjectRepository::setDefaultSessionManager($sessionManager);
		}
		
		return $sessionManager;
	}
}