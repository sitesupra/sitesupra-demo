<?php

use Supra\ObjectRepository\ObjectRepository;
			
$sessionHandler = new Supra\Session\Handler\Internal();

$sessionNamespaceManager = new Supra\Session\SessionNamespaceManager($sessionHandler);
ObjectRepository::setDefaultSessionNamespaceManager($sessionNamespaceManager);

$defaultNamespace = $sessionNamespaceManager->getDefaultSessionNamespace();
ObjectRepository::setDefaultSessionNamespace($defaultNamespace);

