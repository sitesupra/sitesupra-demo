<?php

// Register namespace
$rssNamespace = new Supra\Loader\NamespaceRecord('Project\\Rss', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($rssNamespace);

// Event test
$listenerFunction = function($className, $type, $parameters) {
	\Log::info("Event $type for class $className has been fired with parameters ", $parameters);
};
Supra\Event\Registry::listen('Project\\Rss\\Controller', 'index', $listenerFunction);

// Bind to URL /rss
$cssRouter = new Supra\Router\UriRouter('/rss');
$frontController->route($cssRouter, '\\Project\\Rss\\Controller');