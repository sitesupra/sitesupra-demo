<?php

error_reporting(E_ALL | E_NOTICE);

// TODO: should be read by some component, available by all project
$ini = parse_ini_file(SUPRA_CONF_PATH . 'supra.ini', true);

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

$parser = new Supra\Configuration\Parser\YamlParser();
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Rss/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Pages/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Text/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'DistributedController/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Authentication/config.yml');

// Experimental: should be good if would be able to define extra PHP configuration as well
require_once SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.php';
$parser->parseFile(SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.yml');

$parser->parseFile(SUPRA_WEBROOT_PATH . 'cms/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Locale/config.yml');

