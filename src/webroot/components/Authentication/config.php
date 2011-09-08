<?php

namespace Project\Authentication;

// Bind to URL /cms
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->priority = \Supra\Router\RouterAbstraction::PRIORITY_TOP;
$routerConfiguration->controller = 'Project\Authentication\AuthenticationPreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
