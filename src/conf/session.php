<?php

use Supra\ObjectRepository\ObjectRepository;

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->isDefault = true;
$sessionManagerConfiguration->configure();

$defaultSessionNamespaceConfiguration = new \Supra\Session\Configuration\SessionNamespaceConfiguration();
$defaultSessionNamespaceConfiguration->managerNamespace = '';
$defaultSessionNamespaceConfiguration->isDefault = true;
$defaultSessionNamespaceConfiguration->class = false;
$defaultSessionNamespaceConfiguration->configure();
