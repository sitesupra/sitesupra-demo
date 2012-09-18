<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\User\UserProviderAbstract;
use Supra\Cms\CmsPageLocalizationIndexerQueueListener;
use Supra\Cms\CmsUserSingleSessionListener;
use Supra\Cms\CmsController;
use Supra\Controller\Pages\PageController;
use Supra\Statistics\GoogleAnalytics\Listener\GoogleAnalyticsListener;
use Supra\Controller\Pages\Listener\BlockExecuteListener;
use Supra\Controller\Pages\Listener\PageGroupCacheDropListener;
use Supra\Controller\Pages\Listener\FacebookPagePublishingListener;
use Supra\Controller\Pages\Listener\PageMetadataOutputListener;
use Supra\Controller\Pages\Listener\VersionMetadataHtmlInjection;

$ini = ObjectRepository::getIniConfigurationLoader('');
$eventManager = ObjectRepository::getEventManager();

// Limits one session per user
$userProvider = ObjectRepository::getUserProvider('#cms');
$externalUserProviderActive = $ini->getValue('external_user_database', 'active', false);
if ( ! $externalUserProviderActive) {
	$cmsUserSingleSessionListener = new CmsUserSingleSessionListener();
	$eventManager->listen(UserProvider::EVENT_PRE_SIGN_IN, $cmsUserSingleSessionListener, $userProvider);
}

// Sends email for newly created users
$listener = new \Supra\User\Listener\UserCreateListener();
$eventManager->listen(UserProviderAbstract::EVENT_POST_USER_CREATE, $listener, $userProvider);

// Search index
$listener = new CmsPageLocalizationIndexerQueueListener();
$eventManager->listen(CmsController::EVENT_POST_PAGE_PUBLISH, $listener);
$eventManager->listen(CmsController::EVENT_POST_PAGE_DELETE, $listener);

// Google Analytics
$listener = new GoogleAnalyticsListener();
$eventManager->listen(PageController::EVENT_POST_PREPARE_CONTENT, $listener);

$listener = new VersionMetadataHtmlInjection();
$eventManager->listen(PageController::EVENT_POST_PREPARE_CONTENT, $listener);

// Block execution log
$blockSql = $ini->getValue('log', 'block_log', false);
if ($blockSql) {
	$listener = new BlockExecuteListener();
	$eventManager->listen($listener->getSubscribedEvents(), $listener);
}

// SQL log
$logSql = $ini->getValue('log', 'sql_log', false);
if ($logSql) {
	$listener = new \Supra\Log\Logger\SqlLogger();
	$eventManager->listen($listener->getSubscribedEvents(), $listener);
}

// Drops page cache group
$listener = new PageGroupCacheDropListener();
$eventManager->listen($listener->getSubscribedEvents(), $listener);

// Facebook events
$listener = new FacebookPagePublishingListener();
$eventManager->listen($listener->getSubscribedEvents(), $listener);

// Page metadata output listener
$listener = new PageMetadataOutputListener();
$listener->setUseParentOnEmptyMetadata(true);
$eventManager->listen(PageController::EVENT_POST_PREPARE_CONTENT, $listener);
