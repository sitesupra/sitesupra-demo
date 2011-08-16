<?php

// Register namespace
$rssNamespace = new Supra\Loader\NamespaceRecord('Project\DistributedController', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($rssNamespace);

// Bind to URL /dc
$cssRouter = new Supra\Router\UriRouter('/dc');
$frontController->route($cssRouter, 'Project\DistributedController\DistributedController');
