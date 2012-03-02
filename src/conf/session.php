<?php

use Supra\ObjectRepository\ObjectRepository;

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->sessionExpirationTime = 1440;
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->name = 'SID';
$sessionManagerConfiguration->namespaces = array(
	'Supra\Cms\AuthenticationPreFilterController',
	'Supra\Cms\CmsController',
	'Project\SampleAuthentication\AuthenticateController',
	'Project\SampleAuthentication\AuthenticatePreFilterController',
	'Project\SocialMedia\SocialMediaController',
	'Project\CmsRemoteLogin\Controller',
	'Project\Payment',
);
$sessionManagerConfiguration->configure();

// frontend session manager
$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->sessionExpirationTime = 1440;
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->name = 'fSID';
$sessionManagerConfiguration->namespaces = array(
	'Supra\Controller\Pages',
	'Project\Pages\LoginPreFilterController',
	'Project\Pages',
	'Supra\User',
	'Project\Payment',
);
$sessionManagerConfiguration->configure();