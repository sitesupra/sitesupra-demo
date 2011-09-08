<?php

namespace Supra\Cms;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->controller = 'Supra\Cms\CmsController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
