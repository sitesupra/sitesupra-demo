<?php

// Register namespace
$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\InternalUserManager', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

$router = new Supra\Router\Uri('/cms/internal-user-manager');
$frontController->route($router, 'Supra\Cms\InternalUserManager\Controller');
