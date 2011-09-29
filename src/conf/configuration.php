<?php

error_reporting(E_ALL | E_NOTICE);

// TODO: make something else
//$ini = parse_ini_file(SUPRA_CONF_PATH . 'supra.autobuild.ini', true);
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

$parser = new Supra\Configuration\Parser\YamlParser();
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Rss/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Pages/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Text/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'DistributedController/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Authentication/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'SampleAuthentication/config.yml');
$parser->parseFile(SUPRA_WEBROOT_PATH . 'cms/config.yml');
$parser->parseFile(SUPRA_COMPONENT_PATH . 'Locale/config.yml');

