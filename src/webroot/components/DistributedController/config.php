<?php

namespace Project\DistributedController;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/dc';
$routerConfiguration->controller = 'Project\DistributedController\DistributedController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
