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
	 * @var AuthorizationAccessPolicyAbstraction
	 */
	public $authorizationAccessPolicy;

	/**
	 * Configure
	 */
	public function configure()
	{
		$this->authorizationAccessPolicy->setAppConfig($this);

		$config = CmsApplicationConfiguration::getInstance();
		$config->addConfiguration($this);

		ObjectRepository::setApplicationConfiguration($this->id, $this);
	}
	
	public function getApplicationDataForInternalUserManager() {
		
		return array(
				'id' => $this->id,
				'title' => $this->title,
				'icon' => $this->icon,
				'path' => $this->path,
				'permissions' => array($this->authorizationAccessPolicy->getPermissionForInternalUserManager())
		);
	}
	
	/**
	 * To keep authorization component interface
	 * @return string
	 */
	public function getAuthorizationId()
	{
		return $this->id;
	}

}
