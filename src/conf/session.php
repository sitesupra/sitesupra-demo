<?php

use Supra\ObjectRepository\ObjectRepository;

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->namespaces = array(
	'Supra\Cms\AuthenticationPreFilterController',
	'Supra\Cms\CmsController',
	'Project\SampleAuthentication\AuthenticateController',
	'Project\SampleAuthentication\AuthenticatePreFilterController',
);
$sessionManagerConfiguration->configure();

//$defaultSessionNamespaceConfiguration = new \Supra\Session\Configuration\SessionNamespaceConfiguration();
//$defaultSessionNamespaceConfiguration->managerNamespace = '';
//$defaultSessionNamespaceConfiguration->isDefault = true;
//$defaultSessionNamespaceConfiguration->class = false;
//$defaultSessionNamespaceConfiguration->configure();
