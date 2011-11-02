<?php

use Supra\ObjectRepository\ObjectRepository;
use \Solarium_Client;

$solariumClient = new Solarium_Client(array('adapteroptions' => $ini['solarium']));

ObjectRepository::setDefaultSolariumClient($solariumClient);
