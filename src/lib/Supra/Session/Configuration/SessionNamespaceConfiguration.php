<?php

namespace Supra\Session\Configuration;;

use Supra\Session\SessionNamespaceManager;
use Supra\ObjectRepository\ObjectRepository;

class SessionNamespaceConfiguration 
{
	/**
	 * @var string
	 */
	public $class;
	
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var array
	 */
	public $namespaces = array();
	
	/**
	 * @var string
	 */
	public $managerNamespace;
	
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
		$sessionNamespaceManager = ObjectRepository::getSessionNamespaceManager($this->managerNamespace);
		
		if ($this->class === false) {
			$sessionNamespace = $sessionNamespaceManager->getDefaultSessionNamespace();
		}
		else {
			$sessionNamespace = $sessionNamespaceManager->getOrCreateSessionNamespace($this->name, $this->class);
		}
		
		foreach ($this->namespaces as $namespace) {
			ObjectRepository::setSessionNamespace($namespace, $sessionNamespace);
		}
		
		if ($this->isDefault) {
			ObjectRepository::setDefaultSessionNamespace($sessionNamespace);
		}
	}
}
