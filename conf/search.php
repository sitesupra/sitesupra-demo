<?php

use Supra\ObjectRepository\ObjectRepository;
use \Solarium_Client;
use Supra\Search\SchemaCheckingHttpAdapter;

$ini = ObjectRepository::getIniConfigurationLoader('');
$searchParams = $ini->getSection('solarium');

$adapterClass = '\Solarium_Client_Adapter_Http';

if( ! empty ($searchParams['adapter']) ) {
	$adapterClass = $searchParams['adapter'];
}

$options = array(
	'adapter' => $adapterClass,
	'adapteroptions' => $searchParams
);

$solariumClient = new Solarium_Client($options);

ObjectRepository::setDefaultSolariumClient($solariumClient);
