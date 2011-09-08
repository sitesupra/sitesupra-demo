<?php

use Supra\Loader\Loader;
use Supra\Loader\Configuration\NamespaceConfiguration;

// Components autoloader
$namespaceConfiguration = new NamespaceConfiguration();
$namespaceConfiguration->dir = SUPRA_COMPONENT_PATH;
$namespaceConfiguration->namespace = 'Project';
$namespace = $namespaceConfiguration->configure();
Loader::getInstance()->registerNamespace($namespace);

require_once SUPRA_WEBROOT_PATH . 'cms/CmsNamespaceLoaderStrategy.php';

// CMS autoloader
$namespaceConfiguration = new NamespaceConfiguration();
$namespaceConfiguration->class = 'Supra\Cms\CmsNamespaceLoaderStrategy';
$namespaceConfiguration->dir = SUPRA_WEBROOT_PATH . 'cms';
$namespaceConfiguration->namespace = 'Supra\Cms';
$namespace = $namespaceConfiguration->configure();
Loader::getInstance()->registerNamespace($namespace);