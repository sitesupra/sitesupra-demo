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

	function configure()
	{
		$this->paymentProvider = new Transact\PaymentProvider();
		$this->requestControllerClass = Transact\RequestController::CN();

		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$merchantGuid = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_MERCHANT_GUID);
		$this->paymentProvider->setMerchantGuid($merchantGuid);

		$password = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PASSWORD);
		$this->paymentProvider->setPassword($password);

		$routingString = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_ROUTING_STRING);
		$this->paymentProvider->setRoutingstring($routingString);

		$apiUrl = $iniLoader->getvalue($this->iniSectionName, self::INI_KEY_API_URL);
		$this->paymentProvider->setApiUrl($apiUrl);

		$this->paymentProvider->setIs3dAccount((boolean) $this->is3dAccount);
		$this->paymentProvider->setGatewayCollects((boolean) $this->gatewayCollects);

		$this->paymentProvider->setReturnHost($this->returnHost);
		$this->paymentProvider->setCallbackHost($this->callbackHost);
		$this->paymentProvider->setFormDataPath($this->formDataPath);

		parent::configure();
	}

}
