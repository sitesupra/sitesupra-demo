<?php

namespace Project\Authenticate;

use Supra\ObjectRepository\ObjectRepository;

// Bind controller to URL /authenticate
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/authenticate';
$routerConfiguration->controller = 'Project\Authenticate\AuthenticateController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

unset ($routerConfiguration);
unset ($controllerConfiguration);

// Bind prefilter to URL /authenticate
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/authenticate';
$routerConfiguration->priority = \Supra\Router\RouterAbstraction::PRIORITY_TOP;
$routerConfiguration->controller = 'Project\Authenticate\AuthenticatePreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

$sessionNamespaceManager = ObjectRepository::getSessionNamespaceManager(__NAMESPACE__);

$authenticationSessionNamespace = $sessionNamespaceManager
	->getOrCreateSessionNamespace('Test', 'Project\Authenticate\AuthenticateSessionNamespace');

ObjectRepository::setSessionNamespace(__NAMESPACE__, $authenticationSessionNamespace);