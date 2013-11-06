<?php

namespace Project\Payment\Paypal;

use Supra\Payment;
use Project\Payment\Paypal;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Class Configuration
 * @package Project\Payment\Paypal
 */
class Configuration extends Payment\ConfigurationAbstraction
{
	/**
	 *
	 */
	const INI_KEY_API_USERNAME = 'api_username';
	/**
	 *
	 */
	const INI_KEY_API_PASSWORD = 'api_password';
	/**
	 *
	 */
	const INI_KEY_API_SIGNATURE = 'api_signature';
	/**
	 *
	 */
	const INI_KEY_PAYPAL_REDIRECT_URL = 'paypal_redirect_url';
	/**
	 *
	 */
	const INI_KEY_PAYPAL_API_URL = 'paypal_api_url';

	/**
	 *
	 */
	const INI_KEY_PAYPAL_API_URL_2 = 'paypal_api_url_2';

	/**
	 * 
	 */
	const INI_KEY_APPLICATION_ID = 'application_id';

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
	 * @var
	 */
	public $useXAuthorizationHeader = false;
	
	/**
	 *
	 */
	function configure()
	{
		// Skip configuratin if INI section does not exist
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);
		$section = $iniLoader->getSection($this->iniSectionName, false);
		
		if ( ! empty($section)) {
			$this->paymentProvider = new Paypal\PaymentProvider();
			$this->requestControllerClass = Paypal\RequestController::CN();

			$apiUsername = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_USERNAME);
			$this->paymentProvider->setApiUsername($apiUsername);

			$apiPassword = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_PASSWORD);
			$this->paymentProvider->setApiPassword($apiPassword);

			$apiSignature = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_SIGNATURE);
			$this->paymentProvider->setApiSignature($apiSignature);

			$paypalApiUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PAYPAL_API_URL);
			$this->paymentProvider->setPaypalApiUrl($paypalApiUrl);

			$paypalApiUrl2 = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PAYPAL_API_URL_2);
			$this->paymentProvider->setPaypalApiUrl2($paypalApiUrl2);

			$paypalRedirectUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PAYPAL_REDIRECT_URL);
			$this->paymentProvider->setPaypalRedirectUrl($paypalRedirectUrl);
			
			$applicationId = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_APPLICATION_ID);
			$this->paymentProvider->setApplicationId($applicationId);

			$this->paymentProvider->setReturnHost($this->returnHost);
			$this->paymentProvider->setCallbackHost($this->callbackHost);

			parent::configure();
		}
	}

}
