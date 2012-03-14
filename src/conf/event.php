<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\CmsPageLocalizationIndexerQueueListener;
use Supra\Cms\CmsUserSingleSessionListener;
use Supra\Cms\CmsController;
use Supra\Controller\Pages\PageController;
use Supra\Statistics\GoogleAnalytics\Listener\GoogleAnalyticsListener;
use Supra\Controller\Pages\Listener\BlockExecuteListener;
use Supra\Controller\Pages\Listener\PageGroupCacheDropListener;
use Supra\Controller\Pages\Listener\FacebookPagePublishingListener;

$ini = ObjectRepository::getIniConfigurationLoader('');

/*
 * CMS user provider event manager
 */
$userEventManager = new EventManager();

// Limits one session per user
$externalUserProviderActive = $ini->getValue('external_user_database', 'active', false);
if ( ! $externalUserProviderActive) {
	$cmsUserSingleSessionListener = new CmsUserSingleSessionListener();
	$userEventManager->listen(UserProvider::EVENT_PRE_SIGN_IN, $cmsUserSingleSessionListener);
}

// Sends email for newly created users
$listener = new \Supra\User\Listener\UserCreateListener();
$userEventManager->listen(\Supra\User\UserProviderAbstract::EVENT_POST_USER_CREATE, $listener);

$userProvider = ObjectRepository::getUserProvider('#cms');
ObjectRepository::setEventManager($userProvider, $userEventManager);

/*
 * General event manager
 */
$eventManager = new EventManager();

$listener = new CmsPageLocalizationIndexerQueueListener();
$eventManager->listen(CmsController::EVENT_POST_PAGE_PUBLISH, $listener);
$eventManager->listen(CmsController::EVENT_POST_PAGE_DELETE, $listener);

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
