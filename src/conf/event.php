<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\CmsUserSingleSessionListener;

$userProvider = ObjectRepository::getUserProvider('#cms');

$manager = new EventManager();
$listener = new CmsUserSingleSessionListener();
$manager->listen(UserProvider::EVENT_PRE_SIGN_IN, $listener);

ObjectRepository::setEventManager($userProvider, $manager);
