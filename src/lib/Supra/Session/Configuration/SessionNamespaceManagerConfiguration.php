<?php

namespace Supra\Session\Configuration;

use Supra\Session\SessionNamespaceManager;
use Supra\ObjectRepository\ObjectRepository;

class SessionNamespaceManagerConfiguration 
{
	/**
	 * @var string
	 */
	public $handlerClass;
	
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var array
	 */
	private $namespaces = array();
	
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
		$handler = new $this->handlerClass();
		$sessionNamespaceManager = new SessionNamespaceManager($handler);
		
		foreach($this->namespaces as $namespace) {
			ObjectRepository::setSessionNamespace($namespace, $sessionNamespaceManager);
		}
		
		if($this->isDefault) {
			ObjectRepository::setDefaultSessionNamespaceManager($sessionNamespaceManager);
		}
	}
}