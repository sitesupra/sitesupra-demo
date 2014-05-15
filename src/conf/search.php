<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search;
use Supra\Search\Solarium;

$ini = ObjectRepository::getIniConfigurationLoader('');

$searchParams = $ini->getSection('solarium');

$solariumClient = new \Solarium_Client(array(

	'adapter' => isset($searchParams['adapter'])
			? $searchParams['adapter'] : '\Solarium_Client_Adapter_Http',

	'adapteroptions' => $searchParams,
));

ObjectRepository::setDefaultSearchService(
		new Search\SearchService(new Solarium\SolariumSearcher($solariumClient)));

ObjectRepository::setDefaultIndexerService(
		new Search\IndexerService(new Solarium\SolariumIndexer($solariumClient)));