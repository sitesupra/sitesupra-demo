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
		
		$sessionManager = new SessionManager($handler);
		
		foreach ($this->namespaces as $namespace) {
			ObjectRepository::setSessionManager($namespace, $sessionManager);
		}
		
		if ($this->isDefault) {
			ObjectRepository::setDefaultSessionManager($sessionManager);
		}
	}
}