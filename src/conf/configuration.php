<?php

use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\Loader\WriteableIniConfigurationLoader;

error_reporting(E_ALL | E_NOTICE);

$iniLoader = new Supra\Configuration\Loader\IniConfigurationLoader('supra.ini');
ObjectRepository::setDefaultIniConfigurationLoader($iniLoader);

$writeableIniLoader = new WriteableIniConfigurationLoader('theme.ini');
ObjectRepository::setIniConfigurationLoader('Supra\Controller\Layout\Theme', $writeableIniLoader);

$writeableIniLoader = new WriteableIniConfigurationLoader('supra.ini');
ObjectRepository::setIniConfigurationLoader('Supra\Cms\Settings', $writeableIniLoader);

require_once SUPRA_CONF_PATH . 'loader.php';
require_once SUPRA_CONF_PATH . 'log.php';
require_once SUPRA_CONF_PATH . 'system.php';
require_once SUPRA_CONF_PATH . 'cache.php';
require_once SUPRA_CONF_PATH . 'database.php';
require_once SUPRA_CONF_PATH . 'locale.php';
require_once SUPRA_CONF_PATH . 'filestorage.php';
require_once SUPRA_CONF_PATH . 'user.php';
require_once SUPRA_CONF_PATH . 'external_users_database.php';
require_once SUPRA_CONF_PATH . 'template.php';
require_once SUPRA_CONF_PATH . 'session.php';
require_once SUPRA_CONF_PATH . 'mailer.php';
require_once SUPRA_CONF_PATH . 'authorization.php';
require_once SUPRA_CONF_PATH . 'event.php';
require_once SUPRA_CONF_PATH . 'search.php';
require_once SUPRA_CONF_PATH . 'payment.php';
require_once SUPRA_CONF_PATH . 'cms.php';

$parser = new Supra\Configuration\Parser\YamlParser();
$configLoader = new ComponentConfigurationLoader($parser);
$configLoader->setCacheLevel(ComponentConfigurationLoader::CACHE_LEVEL_EXPIRE_BY_MODIFICATION);

$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'CmsRemoteLogin/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Pages/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Payment/DummyShop/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'BannerMachine/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'DistributedController/config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'config.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.yml');

$configLoader->loadFile(SUPRA_WEBROOT_PATH . 'cms/config.yml');
$configLoader->loadFile(SUPRA_CONF_PATH . 'payment.yml');
$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'SocialMedia/config.yml');

$configLoader->loadFile(SUPRA_COMPONENT_PATH . 'Ajax/config.yml');
$configLoader->loadFile(SUPRA_LIBRARY_PATH . 'Supra/Social/Facebook/config.yml');
$configLoader->loadFile(SUPRA_WEBROOT_PATH . 'assets/js/config.yml');
