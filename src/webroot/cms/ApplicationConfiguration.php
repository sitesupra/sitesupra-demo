<?php

namespace Supra\Cms;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Configuration\ComponentConfiguration;

/**
 * ApplicationConfiguration
 *
 */
class ApplicationConfiguration extends ComponentConfiguration
{

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
	public $url;

	/**
	 * @var AuthorizationAccessPolicyAbstraction
	 */
	public $authorizationAccessPolicy;

	/**
	 * Configure
	 */
	public function configure()
	{
		parent::configure();

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
			//TODO: hardcoded CMS URL
			'path' => '/cms/' . $this->url,
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
