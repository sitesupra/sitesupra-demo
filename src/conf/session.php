<?php

use Supra\ObjectRepository\ObjectRepository;

require_once SUPRA_COMPONENT_PATH . 'Authentication/AuthenticationSessionNamespace.php';

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->namespaces = array(
	'Supra\Cms\CmsController',
	'Project\Authentication\AuthenticationPreFilterController',
	'Project\SampleAuthentication\AuthenticateController',
	'Project\SampleAuthentication\AuthenticatePreFilterController',
);
$sessionManagerConfiguration->configure();

//$defaultSessionNamespaceConfiguration = new \Supra\Session\Configuration\SessionNamespaceConfiguration();
//$defaultSessionNamespaceConfiguration->managerNamespace = '';
//$defaultSessionNamespaceConfiguration->isDefault = true;
//$defaultSessionNamespaceConfiguration->class = false;
//$defaultSessionNamespaceConfiguration->configure();
