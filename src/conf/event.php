<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;

$userProvider = ObjectRepository::getUserProvider('#cms');

$manager = new EventManager();
//$manager->listen($eventTypes, $callBack);

ObjectRepository::setEventManager($userProvider, $manager);
