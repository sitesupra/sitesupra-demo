<?php

namespace Project\Rss;

$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/rss';
$routerConfiguration->controller = 'Project\Rss\Controller';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

// Event test
//TODO: make this somehow configurable
$listenerFunction = function($className, $type, $parameters) {
	\Log::info("Event $type for class $className has been fired with parameters ", $parameters);
};
\Supra\Event\Registry::listen('Project\Rss\Controller', 'index', $listenerFunction);
