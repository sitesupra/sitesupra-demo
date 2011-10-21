<?php

namespace Project\Rss;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Event\EventManager;

// Event test
//TODO: make this somehow configurable
$listenerFunction = function(\Supra\Event\EventArgs $eventArgs) {
	\Log::warn("Event has been fired with parameters ", $eventArgs);
};

$eventManager = new EventManager();
$eventManager->listen('index', $listenerFunction);

ObjectRepository::setEventManager('Project\Rss\Controller', $eventManager);
