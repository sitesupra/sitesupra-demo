<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\CmsPageLocalizationIndexerQueueListener;
use Supra\Cms\CmsUserSingleSessionListener;
use Supra\Cms\CmsController;

$eventManager = new EventManager();

$userProvider = ObjectRepository::getUserProvider('#cms');

$cmsUserSingleSessionListener = new CmsUserSingleSessionListener();
$eventManager->listen(UserProvider::EVENT_PRE_SIGN_IN, $cmsUserSingleSessionListener);

ObjectRepository::setEventManager($userProvider, $eventManager);


$eventManager = new EventManager();

$listener = new CmsPageLocalizationIndexerQueueListener();
$eventManager->listen(CmsController::EVENT_POST_PAGE_PUBLISH, $listener);

ObjectRepository::setEventManager('Supra\Cms\ContentManager', $eventManager);
