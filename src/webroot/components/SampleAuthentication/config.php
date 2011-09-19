<?php

namespace Project\SampleAuthentication;

use Supra\ObjectRepository\ObjectRepository;

const CONTROLLER_URL = 'auth-sample';

// Bind controller to URL /authenticate
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = CONTROLLER_URL;
$routerConfiguration->controller = 'Project\SampleAuthentication\AuthenticateController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

unset ($routerConfiguration);
unset ($controllerConfiguration);

// Bind prefilter to URL /authenticate
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = CONTROLLER_URL;
$routerConfiguration->priority = \Supra\Router\RouterAbstraction::PRIORITY_TOP;
$routerConfiguration->controller = 'Project\SampleAuthentication\AuthenticatePreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

$sessionNamespaceManager = ObjectRepository::getSessionNamespaceManager(__NAMESPACE__);

$authenticationSessionNamespace = $sessionNamespaceManager
	->getOrCreateSessionNamespace('Test', 'Project\SampleAuthentication\AuthenticateSessionNamespace');

ObjectRepository::setSessionNamespace(__NAMESPACE__, $authenticationSessionNamespace);