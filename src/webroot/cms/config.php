<?php

namespace Supra\Cms;

// Load loader manually and configure
require_once __DIR__ . DIRECTORY_SEPARATOR . 'CmsNamespaceRecord.php';
$namespaceConfiguration = new \Supra\Loader\Configuration\NamespaceConfiguration();
$namespaceConfiguration->class = 'Supra\Cms\CmsNamespaceRecord';
$namespaceConfiguration->dir = __DIR__;
$namespaceConfiguration->namespace = __NAMESPACE__;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->controller = 'Supra\Cms\CmsController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->namespace = $namespaceConfiguration;
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
