<?php

// Register namespace
$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

// TODO: temporary solution for namespace autoloading for folders with "-"
$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\ContentManager', __DIR__ . '/content-manager');
Supra\Loader\Registry::getInstance()->registerNamespace($namespace);
$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\InternalUserManager', __DIR__ . '/internal-user-manager');
Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

$router = new Supra\Router\Uri('/cms');
$frontController->route($router, 'Supra\Cms\CmsController');
