<?php

namespace Project\Pages;

$namespaceConfiguration = new \Supra\Loader\Configuration\NamespaceConfiguration();
$namespaceConfiguration->dir = __DIR__;
$namespaceConfiguration->namespace = __NAMESPACE__;

$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/';
$routerConfiguration->controller = 'Project\Pages\PageController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->namespace = $namespaceConfiguration;
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();
