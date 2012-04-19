<?php

namespace Project\Payment\Transact;

use Supra\Payment;
use Project\Payment\Transact;
use Supra\ObjectRepository\ObjectRepository;

class Configuration extends Payment\ConfigurationAbstraction
{

	const INI_KEY_MERCHANT_GUID = 'merchant_guid';
	const INI_KEY_PASSWORD = 'password';
	const INI_KEY_ROUTING_STRING = 'routing_string';
	const INI_KEY_API_URL = 'api_url';
	const INI_KEY_USER_IP_OVERRIDE = 'user_ip_override';
	const INI_KEY_RECURRENT_ROUTING_STRING = 'recurrent_routing_string';

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
	public $is3dAccount;

	/**
	 * @var string
	 */
	public $gatewayCollects;

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
			$this->paymentProvider = new Transact\PaymentProvider();
			$this->requestControllerClass = Transact\RequestController::CN();

			$merchantGuid = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_MERCHANT_GUID);
			$this->paymentProvider->setMerchantGuid($merchantGuid);

			$password = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PASSWORD);
			$this->paymentProvider->setPassword($password);

			$routingString = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_ROUTING_STRING);
			$this->paymentProvider->setRoutingstring($routingString);

			$recurrentRoutingString = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_RECURRENT_ROUTING_STRING);
			$this->paymentProvider->setRecurrentRoutingString($recurrentRoutingString);

			$apiUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_URL);
			$this->paymentProvider->setApiUrl($apiUrl);

			$userIpOverride = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_USER_IP_OVERRIDE, null);
			$this->paymentProvider->setUserIpOverride($userIpOverride);

			$this->paymentProvider->setIs3dAccount((boolean) $this->is3dAccount);
			$this->paymentProvider->setGatewayCollects((boolean) $this->gatewayCollects);

			$this->paymentProvider->setReturnHost($this->returnHost);
			$this->paymentProvider->setCallbackHost($this->callbackHost);
			$this->paymentProvider->setFormDataPath($this->formDataPath);

			parent::configure();
		}
	}

}
