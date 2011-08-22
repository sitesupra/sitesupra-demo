<?php

namespace Project\DistributedController;

// Register namespace
$namespaceConfiguration = new \Supra\Loader\Configuration\NamespaceConfiguration();
$namespaceConfiguration->dir = __DIR__;
$namespaceConfiguration->namespace = __NAMESPACE__;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/dc';
$routerConfiguration->controller = 'Project\DistributedController\DistributedController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->namespace = $namespaceConfiguration;
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
