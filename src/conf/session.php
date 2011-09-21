<?php

use Supra\ObjectRepository\ObjectRepository;

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

$sessionNamespaceManagerConfiguration = new \Supra\Session\Configuration\SessionNamespaceManagerConfiguration();
$sessionNamespaceManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionNamespaceManagerConfiguration->isDefault = true;
$sessionNamespaceManagerConfiguration->configure();

$defaultSessionNamespaceConfiguration = new \Supra\Session\Configuration\SessionNamespaceConfiguration();
$defaultSessionNamespaceConfiguration->managerNamespace = '';
$defaultSessionNamespaceConfiguration->isDefault = true;
$defaultSessionNamespaceConfiguration->class = false;
$defaultSessionNamespaceConfiguration->configure();
