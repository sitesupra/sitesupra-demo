<?php

namespace Project\Payment\Dengi\Configuration;

use Supra\Payment;
use Project\Payment\Dengi;
use Supra\ObjectRepository\ObjectRepository;

class ProviderConfiguration extends Payment\ConfigurationAbstraction
{

	const INI_KEY_PROJECT_ID = 'project_id';
	const INI_KEY_SOURCE = 'source';
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
	public $dataFormPath;

	/**
	 * @var string
	 */
	public $userIpOverride;
	public $backends;

	function configure()
	{
		// Skip configuratin if INI section does not exist
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);
		$section = $iniLoader->getSection($this->iniSectionName, false);

		if ( ! empty($section)) {

			$paymentProvider = new Dengi\PaymentProvider();

			$this->requestControllerClass = Dengi\RequestController::CN();

			$secret = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_SECRET);
			$paymentProvider->setSecret($secret);

			$projectId = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_PROJECT_ID);
			$paymentProvider->setProjectId($projectId);

			$source = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_SOURCE);
			$paymentProvider->setSource($source);

			$apiUrl = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_API_URL);
			$paymentProvider->setApiUrl($apiUrl);

			$userIpOverride = $iniLoader->getValue($this->iniSectionName, self::INI_KEY_USER_IP_OVERRIDE, null);
			$paymentProvider->setUserIpOverride($userIpOverride);

			$paymentProvider->setReturnHost($this->returnHost);
			$paymentProvider->setCallbackHost($this->callbackHost);
			$paymentProvider->setDataFormPath($this->dataFormPath);

			$backends = $this->getBackends();

			$paymentProvider->setBackends($backends);

			$this->paymentProvider = $paymentProvider;

			parent::configure();
		}
	}

	/**
	 * @return array
	 */
	protected function getBackends()
	{
		$backendInstances = array();

		foreach ($this->backends as $backendConfiguration) {
			/* @var $backendConfiguration BackendConfiguration */

			if ( ! empty($backendInstances[$backendConfiguration->modeType])) {
				throw new Dengi\Exception\ConfigurationException('Already have backend with mode type "' . $backendConfiguration->modeType . '".');
			}

			$backendInstance = $backendConfiguration->getBackendInstance();

			$backendInstances[$backendConfiguration->modeType] = $backendInstance;
		}

		return $backendInstances;
	}

}
