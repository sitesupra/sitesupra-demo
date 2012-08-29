<?php

namespace Project\Payment\Transact;

use Supra\Payment;
use Project\Payment\Transact;
use Supra\ObjectRepository\ObjectRepository;

class Configuration extends Payment\ConfigurationAbstraction
{

	const INI_KEY_PROJECT_ID = 'project_id';
	const INI_KEY_SECRET = 'secret';	
	const INI_KEY_API_URL = 'api_url';
	const INI_KEY_USER_IP_OVERRIDE = 'user_ip_override';
	
	/**
	 * @var string
	 */
	public $iniSectionName;

	/**
	 * @var string
	 */
	public $returnHost;

	/**
	 * @var string
	 */
	public $callbackHost;

	/**
	 * @var string
	 */
	public $formDataPath;

	/**
	 * @var string
	 */
	public $userIpOverride;

	function configure()
	{
		// Skip configuratin if INI section does not exist
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);
		$section = $iniLoader->getSection($this->iniSectionName, false);
		
		if ( ! empty($section)) {
			$this->paymentProvider = new Dengi\PaymentProvider();
			$this->requestControllerClass = Dengi\RequestController::CN();

			$secret = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_SECRET);
			$this->paymentProvider->setSecret($secret);

			$projectId = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PROJECT_ID);
			$this->paymentProvider->setProjectId($projectId);

			$apiUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_URL);
			$this->paymentProvider->setApiUrl($apiUrl);

			$userIpOverride = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_USER_IP_OVERRIDE, null);
			$this->paymentProvider->setUserIpOverride($userIpOverride);

			$this->paymentProvider->setReturnHost($this->returnHost);
			$this->paymentProvider->setCallbackHost($this->callbackHost);
			$this->paymentProvider->setFormDataPath($this->formDataPath);

			parent::configure();
		}
	}

}
