<?php

use Supra\Loader\Registry;
use Supra\Loader\Configuration\NamespaceConfiguration;

// Components autoloader
$namespaceConfiguration = new NamespaceConfiguration();
$namespaceConfiguration->dir = SUPRA_COMPONENT_PATH;
$namespaceConfiguration->namespace = 'Project';
$namespace = $namespaceConfiguration->configure();
Registry::getInstance()->registerNamespace($namespace);

require_once SUPRA_WEBROOT_PATH . 'cms/CmsNamespaceRecord.php';

// CMS autoloader
$namespaceConfiguration = new NamespaceConfiguration();
$namespaceConfiguration->class = 'Supra\Cms\CmsNamespaceRecord';
$namespaceConfiguration->dir = SUPRA_WEBROOT_PATH . 'cms';
$namespaceConfiguration->namespace = 'Supra\Cms';
$namespace = $namespaceConfiguration->configure();
Registry::getInstance()->registerNamespace($namespace);