<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Session\SessionManagerEventListener;
use Supra\Controller\FrontController;

$eventManager = ObjectRepository::getEventManager();

$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->sessionExpirationTime = 1440;
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->name = 'SID';
$sessionManagerConfiguration->namespaces = array(
	'Supra\Cms\AuthenticationPreFilterController',
	'Supra\Cms\CmsController',
	'Project\SampleAuthentication\SampleAuthenticationController',
	'Project\SampleAuthentication\SampleAuthenticationPreFilter',
	'Project\SocialMedia\SocialMediaController',
	'Project\CmsRemoteLogin\Controller',
	'Project\Payment',
);
$sessionManager = $sessionManagerConfiguration->configure();

$listener = new SessionManagerEventListener($sessionManager);
$eventManager->listen(FrontController::EVENT_FRONTCONTROLLER_SHUTDOWN, $listener);

// frontend session manager
$sessionManagerConfiguration = new \Supra\Session\Configuration\SessionManagerConfiguration();
$sessionManagerConfiguration->handlerClass = 'Supra\Session\Handler\PhpSessionHandler';
$sessionManagerConfiguration->sessionExpirationTime = 1440;
$sessionManagerConfiguration->isDefault = false;
$sessionManagerConfiguration->name = 'fSID';
$sessionManagerConfiguration->namespaces = array(
	'Project\Blocks\Login\LoginPreFilterController',
	'Project\Blocks\Login\LoginBlock',
	'Supra\User',
	'Project\Payment',
);
$sessionManager = $sessionManagerConfiguration->configure();

$listener = new SessionManagerEventListener($sessionManager);
$eventManager->listen(FrontController::EVENT_FRONTCONTROLLER_SHUTDOWN, $listener);
