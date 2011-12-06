<?php

namespace Project\Payment\Paypal;

use Supra\Payment;
use Project\Payment\Paypal;
use Supra\ObjectRepository\ObjectRepository;

class Configuration extends Payment\ConfigurationAbstraction
{
	const INI_KEY_API_USERNAME = 'api_username';
	const INI_KEY_API_PASSWORD = 'api_password';
	const INI_KEY_API_SIGNATURE = 'api_signature';
	const INI_KEY_PAYPAL_REDIRECT_URL = 'paypal_redirect_url';
	const INI_KEY_PAYPAL_API_URL = 'paypal_api_url';

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

	function configure()
	{
		$this->paymentProvider = new Paypal\PaymentProvider();
		$this->requestControllerClass = Paypal\RequestController::CN();

		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$apiUsername = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_USERNAME);
		$this->paymentProvider->setApiUsername($apiUsername);
		
		$apiPassword = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_PASSWORD);
		$this->paymentProvider->setApiPassword($apiPassword);

		$apiSignature = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_SIGNATURE);
		$this->paymentProvider->setApiSignature($apiSignature);

		$paypalApiUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PAYPAL_API_URL);
		$this->paymentProvider->setPaypalApiUrl($paypalApiUrl);

		$paypalRedirectUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PAYPAL_REDIRECT_URL);
		$this->paymentProvider->setPaypalRedirectUrl($paypalRedirectUrl);

		$this->paymentProvider->setReturnHost($this->returnHost);
		$this->paymentProvider->setCallbackHost($this->callbackHost);

		parent::configure();
	}

}
