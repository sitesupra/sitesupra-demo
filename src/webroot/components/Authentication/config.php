<?php

namespace Project\Authentication;

use Supra\ObjectRepository\ObjectRepository;

// Bind to URL /cms
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->priority = \Supra\Router\RouterAbstraction::PRIORITY_TOP;
$routerConfiguration->controller = 'Project\Authentication\AuthenticationPreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

$authenticationSessionNamespaceConfiguration = new \Supra\Session\Configuration\SessionNamespaceConfiguration();
$authenticationSessionNamespaceConfiguration->managerNamespace = __NAMESPACE__;
$authenticationSessionNamespaceConfiguration->name = 'Cms';
$authenticationSessionNamespaceConfiguration->class = 'Project\Authentication\AuthenticationSessionNamespace';
$authenticationSessionNamespaceConfiguration->addNamespace(__NAMESPACE__);
$authenticationSessionNamespaceConfiguration->addNamespace('Supra\Cms');
$authenticationSessionNamespaceConfiguration->configure();
