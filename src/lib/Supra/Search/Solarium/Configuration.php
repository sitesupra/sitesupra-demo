<?php

namespace Supra\Search\Solarium;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use \Solarium_Client;
use Supra\Search\SchemaCheckingHttpAdapter;

class Configuration implements ConfigurationInterface
{
	const FAILED_TO_GET_CLIENT_MESSAGE = 'Failed to get Solr instance. Assuming that indexing is turned off.';

	public $defaultAdapterClass = '\Solarium_Client_Adapter_Http';

	public function configure()
	{
		try {
			$ini = ObjectRepository::getIniConfigurationLoader('');
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
		} catch (\Exception $e) {
			// since indexing can be turned off, sending debug message not error
			\Log::debug(self::FAILED_TO_GET_CLIENT_MESSAGE . PHP_EOL . $e->__toString());
		}
	}

}