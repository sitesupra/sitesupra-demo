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

$eventManager = new EventManager();

$userProvider = ObjectRepository::getUserProvider('#cms');

$cmsUserSingleSessionListener = new CmsUserSingleSessionListener();
$eventManager->listen(UserProvider::EVENT_PRE_SIGN_IN, $cmsUserSingleSessionListener);

ObjectRepository::setEventManager($userProvider, $eventManager);

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

ObjectRepository::setDefaultEventManager($eventManager);
