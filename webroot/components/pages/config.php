<?php

// Register namespace
$namespace = new \Supra\Loader\NamespaceRecord('Project\\Pages', __DIR__);
\Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

// Bind to URL
$router = new \Supra\Controller\Router\Uri('/');
$frontController->route($router, '\\Project\\Pages\\Controller');