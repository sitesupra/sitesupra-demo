<?php

namespace Project\Authentication;

// Register namespace
$namespaceConfiguration = new \Supra\Loader\Configuration\NamespaceConfiguration();
$namespaceConfiguration->dir = __DIR__;
$namespaceConfiguration->namespace = __NAMESPACE__;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->priority = \Supra\Router\RouterAbstraction::PRIORITY_TOP;
$routerConfiguration->controller = 'Project\Authentication\AuthenticationPreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->namespace = $namespaceConfiguration;
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
