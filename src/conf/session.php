<?php

use Supra\ObjectRepository\ObjectRepository;
			
$sessionHandler = new Supra\Session\Handler\PhpSessionHandler();

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

$sessionNamespaceManager = new Supra\Session\SessionNamespaceManager($sessionHandler);
ObjectRepository::setDefaultSessionNamespaceManager($sessionNamespaceManager);

$defaultNamespace = $sessionNamespaceManager->getDefaultSessionNamespace();
ObjectRepository::setDefaultSessionNamespace($defaultNamespace);