<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;

$userProvider = ObjectRepository::getUserProvider('#cms');

$manager = new EventManager();
$manager->listen($element, $eventTypes, $callBack);

ObjectRepository::setEntityManager($userProvider, $manager);
