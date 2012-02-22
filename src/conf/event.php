<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\CmsPageLocalizationIndexerQueueListener;
use Supra\Cms\CmsUserSingleSessionListener;
use Supra\Cms\CmsController;
use Supra\Controller\Pages\PageController;
use Project\GoogleAnalytics\GoogleAnalyticsListener;
use Supra\Controller\Pages\Listener\BlockExecuteListener;
use Supra\Controller\Pages\Listener\PageGroupCacheDropListener;
use Supra\Controller\Pages\Listener\FacebookPagePublishingListener;
use Supra\Configuration\Exception\ConfigurationMissing;

$eventManager = new EventManager();

$userProvider = ObjectRepository::getUserProvider('#cms');

$ini = ObjectRepository::getIniConfigurationLoader('');

try {
	$connectionOptions = $ini->getSection('external_user_database');
	if ( ! $connectionOptions['active']) {
		throw new ConfigurationMissing('');
	}
	
} catch (ConfigurationMissing $e) {
	$cmsUserSingleSessionListener = new CmsUserSingleSessionListener();
	$eventManager->listen(UserProvider::EVENT_PRE_SIGN_IN, $cmsUserSingleSessionListener);
}

ObjectRepository::setEventManager($userProvider, $eventManager);

$eventManager = new EventManager();

$listener = new CmsPageLocalizationIndexerQueueListener();
$eventManager->listen(CmsController::EVENT_POST_PAGE_PUBLISH, $listener);
$eventManager->listen(CmsController::EVENT_POST_PAGE_DELETE, $listener);

// Sends email for newly created users
$listener = new \Supra\Cms\CmsUserCreateListener();
$eventManager->listen(CmsController::EVENT_POST_USER_CREATE, $listener);

// Google Analytics
$listener = new GoogleAnalyticsListener();
$eventManager->listen(PageController::EVENT_POST_PREPARE_CONTENT, $listener);

$blockSql = $ini->getValue('log', 'block_log', false);
if ($blockSql) {
	$listener = new BlockExecuteListener();
	$eventManager->listen($listener->getSubscribedEvents(), $listener);
}

$logSql = $ini->getValue('log', 'sql_log', false);
if ($logSql) {
	$listener = new \Supra\Log\Logger\SqlLogger();
	$eventManager->listen($listener->getSubscribedEvents(), $listener);
}

$listener = new PageGroupCacheDropListener();
$eventManager->listen($listener->getSubscribedEvents(), $listener);

$listener = new FacebookPagePublishingListener();
$eventManager->listen($listener->getSubscribedEvents(), $listener);

ObjectRepository::setDefaultEventManager($eventManager);
