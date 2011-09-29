<?php

namespace Supra\Authorization;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\ControllerAbstraction;

class AuthorizedNamespace
{
	/**
	 * @var string
	 */
	private $namespace;
	
	public function __construct($namespaceOrController) 
	{
		if ($namespaceOrController instanceof ControllerAbstraction) {
			
			$applicationConfiguration = ObjectRepository::getApplicationConfiguration($namespaceOrController);
			
			$this->namespace = $applicationConfiguration->applicationNamespace;
		}
		else if (class_exists($namespace)) { 
			$this->namespace = $namespace;
		}
		else {
			/* TODO: use propper exception */
			throw new \Exception('not a controller or existing class/namespace');
		}
	}
	
	function getAuthorizationId() 
	{
		return $this->namespace;
	}
}
