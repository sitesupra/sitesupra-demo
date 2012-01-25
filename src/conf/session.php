<?php

use Supra\ObjectRepository\ObjectRepository;

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->sessionExpirationTime = 1440;
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->namespaces = array(
	'Supra\Cms\AuthenticationPreFilterController',
	'Supra\Cms\CmsController',
	'Project\SampleAuthentication\AuthenticateController',
	'Project\SampleAuthentication\AuthenticatePreFilterController',
	'Project\SocialMedia\SocialMediaController',
);
$sessionManagerConfiguration->configure();
