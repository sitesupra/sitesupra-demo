<?php

// Register namespace
$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\ContentManager', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

$router = new Supra\Router\Uri('/cms/content-manager');
$frontController->route($router, 'Supra\Cms\ContentManager\Controller');
