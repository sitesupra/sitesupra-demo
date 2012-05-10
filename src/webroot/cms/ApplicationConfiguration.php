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
	 * Controller classname
	 * @var string
	 */
	public $classname;
	
	/**
	 * When the application must be disabled entirely
	 * @var boolean
	 */
	public $disable = false;

	/**
	 * Configure
	 */
	public function configure()
	{
		parent::configure();

		if ($this->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			$this->authorizationAccessPolicy->setAppConfig($this);
		}

		$config = CmsApplicationConfiguration::getInstance();
		
		if ($this->disable) {
			$config->removeConfiguration($this);
		} else {
			$config->addConfiguration($this);
		}

		ObjectRepository::setApplicationConfiguration($this->id, $this);
	}
	
	/**
	 * @return array
	 */
	public function getApplicationDataForInternalUserManager()
	{
		$array = array(
			'id' => $this->id,
			'title' => $this->title,
			'icon' => $this->icon,
			//TODO: hardcoded CMS URL
			'path' => '/' . SUPRA_CMS_URL . '/' . $this->url . '/',
		);
		
		if ($this->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			$array['permissions'] = array($this->authorizationAccessPolicy->getPermissionForInternalUserManager());
		}
		
		return $array;
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
