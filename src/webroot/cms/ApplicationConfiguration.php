<?php

namespace Supra\Cms;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;

/**
 * ApplicationConfiguration
 *
 */
class ApplicationConfiguration implements ConfigurationInterface
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
	 */
	public function configure()
	{
		$ap = ObjectRepository::getAuthorizationProvider($this->applicationNamespace);

		$this->authorizationAccessPolicy = Loader::getClassInstance($this->authorizationAccessPolicyClass, 
				'Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction');
		$this->authorizationAccessPolicy->setAuthorizationProvider($ap);
		$this->authorizationAccessPolicy->setAppConfig($this);
		$this->authorizationAccessPolicy->configure();

		array_unshift(
				$this->permissions, $this->authorizationAccessPolicy->getPermissionForInternalUserManager()
		);

		$config = CmsApplicationConfiguration::getInstance();
		$config->addConfiguration($this);

		ObjectRepository::setApplicationConfiguration($this->applicationNamespace, $this);
	}

}
