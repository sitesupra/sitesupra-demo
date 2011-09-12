<?php

namespace Project\Locale;

$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->class = 'Supra\Router\TopPriorityRouter';
$routerConfiguration->controller = 'Project\Locale\LocalePreFilterController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();