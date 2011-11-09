<?php

use Supra\ObjectRepository\ObjectRepository;
use \Solarium_Client;
use Supra\Search\SchemaCheckingHttpAdapter;

$adapterClass = '\Solarium_Client_Adapter_Http';

if( ! empty ($ini['solarium']['adapter']) ) {
	$adapterClass = $ini['solarium']['adapter'];
}

$options = array(
		'adapter' => $adapterClass,
		'adapteroptions' => $ini['solarium']
);

$solariumClient = new Solarium_Client($options);

ObjectRepository::setDefaultSolariumClient($solariumClient);
