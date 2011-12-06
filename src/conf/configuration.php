<?php

error_reporting(E_ALL | E_NOTICE);

$iniParser = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini');
Supra\ObjectRepository\ObjectRepository::setDefaultIniConfigurationLoader($iniParser);

require_once SUPRA_CONF_PATH . 'loader.php';
require_once SUPRA_CONF_PATH . 'log.php';
require_once SUPRA_CONF_PATH . 'database.php';
require_once SUPRA_CONF_PATH . 'locale.php';
require_once SUPRA_CONF_PATH . 'filestorage.php';
require_once SUPRA_CONF_PATH . 'user.php';
require_once SUPRA_CONF_PATH . 'template.php';
require_once SUPRA_CONF_PATH . 'session.php';
require_once SUPRA_CONF_PATH . 'mailer.php';
require_once SUPRA_CONF_PATH . 'authorization.php';
require_once SUPRA_CONF_PATH . 'event.php';
require_once SUPRA_CONF_PATH . 'search.php';
require_once SUPRA_CONF_PATH . 'payment.php';

$parser = new Supra\Configuration\Parser\YamlParser();
$configLoader = new \Supra\Configuration\Loader\ComponentConfigurationLoader($parser);
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Rss/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Pages/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Text/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Search/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Payment/DummyShop/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'DistributedController/config.yml');

// Experimental: should be good if would be able to define extra PHP configuration as well
require_once SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.php';
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.yml');

$configLoader->loadFile(SUPRA_WEBROOT_PATH . 'cms/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Locale/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Languages/config.yml');
