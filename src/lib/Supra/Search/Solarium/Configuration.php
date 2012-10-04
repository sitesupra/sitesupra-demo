<?php

namespace Supra\Search\Solarium;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use \Solarium_Client;

class Configuration implements ConfigurationInterface
{
	const FAILED_TO_GET_CLIENT_MESSAGE = 'Solr search engine is not configured.';

	public $defaultAdapterClass = '\Solarium_Client_Adapter_Http';

	public function configure()
	{
		$ini = ObjectRepository::getIniConfigurationLoader('');

		if ( ! $ini->hasSection('solarium')) {
			\Log::debug(self::FAILED_TO_GET_CLIENT_MESSAGE);
			return;
		}

		$searchParams = $ini->getSection('solarium');

		$adapterClass = $this->defaultAdapterClass;

		if ( ! empty($searchParams['adapter'])) {
			$adapterClass = $searchParams['adapter'];
		}

		$options = array(
			'adapter' => $adapterClass,
			'adapteroptions' => $searchParams
		);

		$solariumClient = new Solarium_Client($options);

		ObjectRepository::setDefaultSolariumClient($solariumClient);
	}

}
