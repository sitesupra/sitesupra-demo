<?php

namespace Supra\Cms;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;

/**
 * ApplicationConfiguration
 *
 */
class ApplicationConfiguration 
{

	/**
	 * Application ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Application title
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * Application icon path
	 *
	 * @var string
	 */
	public $icon;
	
	/**
	 * Application path
	 *
	 * @var string
	 */
	public $path;

	/**
	 * 
	 * @var array
	 */
	public $permissions = array();
	
	/**
	 * @var AuthorizationAccessPolicyAbstraction
	 */
	public $authorizationAccessPolicy;
	
	/**
	 * @var String
	 */
	public $authorizationAccessPolicyClass;
	
	/**
	 * @var string
	 */
	public $applicationNamespace;
	
	/**
	 * Configure
	 * 
	 */
	public function configure() 
	{
		if ( ! class_exists($this->authorizationAccessPolicyClass)) {
			throw new \RuntimeException('Invalid CMS application configuration, bad/nx authorization access policy class ' . $this->authorizationAccessPolicyClass);
		}
		
		$this->authorizationAccessPolicy = new $this->authorizationAccessPolicyClass();
		
		array_unshift($this->permissions, $this->authorizationAccessPolicy->getAccessPermission());
		
		$config = CmsApplicationConfiguration::getInstance();
		$config->addConfiguration($this);
		
		ObjectRepository::setApplicationConfiguration($this->applicationNamespace, $this);
	}
}
