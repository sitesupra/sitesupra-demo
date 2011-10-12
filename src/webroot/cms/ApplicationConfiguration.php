<?php

namespace Supra\Cms;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Configuration\ConfigurationInterface;

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
		if (!class_exists($this->authorizationAccessPolicyClass)) {
			throw new \RuntimeException('Invalid CMS application configuration, bad/nx authorization access policy class ' . $this->authorizationAccessPolicyClass);
		}

		$ap = ObjectRepository::getAuthorizationProvider($this->applicationNamespace);

		$this->authorizationAccessPolicy = new $this->authorizationAccessPolicyClass();
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
