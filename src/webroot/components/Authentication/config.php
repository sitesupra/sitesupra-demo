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

$sessionNamespaceManager = ObjectRepository::getSessionNamespaceManager(__NAMESPACE__);

if( ! $sessionNamespaceManager->sessionNamespaceIsRegistered('AuthenticationNamespace')) {
	
	$authenticationSessionNamespace = new AuthenticationSessionNamespace('AuthenticationNamespace');
	$sessionNamespaceManager->registerSessionNamespace($authenticationSessionNamespace);
}
else {
	$authenticationSessionNamespace = $sessionNamespaceManager->getSessionNamespace('AuthenticationNamespace');
}

ObjectRepository::setSessionNamespace(__NAMESPACE__, $authenticationSessionNamespace);
ObjectRepository::setSessionNamespace('Supra\Cms\Logout', $authenticationSessionNamespace);
