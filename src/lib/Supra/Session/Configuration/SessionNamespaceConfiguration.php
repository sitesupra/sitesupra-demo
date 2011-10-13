<?php

namespace Supra\Session\Configuration;;

use Supra\Session\SessionManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\ConfigurationInterface;

class SessionNamespaceConfiguration implements ConfigurationInterface
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
		$sessionManager = ObjectRepository::getSessionManager($this->managerNamespace);
		$sessionNamespace = null;
		
		if ($this->class === false) {
			$sessionNamespace = $sessionManager->getDefaultSessionNamespace();
		}
		else {
			$sessionNamespace = $sessionManager->getOrCreateSessionNamespace($this->name, $this->class);
		}
		
		foreach ($this->namespaces as $namespace) {
			ObjectRepository::setSessionNamespace($namespace, $sessionNamespace);
		}
		
		if ($this->isDefault) {
			ObjectRepository::setDefaultSessionNamespace($sessionNamespace);
		}
	}
}
