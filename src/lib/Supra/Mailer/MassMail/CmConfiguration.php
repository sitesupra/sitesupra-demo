<?php

namespace Supra\Mailer\CampaignMonitor;


use Supra\Mailer\CampaignMonitor;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;

class CmConfiguration implements ConfigurationInterface
{
	/**
	 * API key
	 * @var string
	 */
	public $apiKey;
	
	/**
	 * Current client id
	 * @var string
	 */
	public $currentClientId;
	
	/**
	 * Sets configuration for Campaign Monitor API
	 */
	public function configure()
	{
		$api = new CmApi();
		$api->setApiKey($this->apiKey);
		$api->getCurrentClientId($this->currentClientId);
		
		\Supra\ObjectRepository\ObjectRepository::setDefaultCampaignMonitorApi($api);
		
	}
	
	
}
