<?php

namespace Project\Rss;

// Event test
//TODO: make this somehow configurable
$listenerFunction = function($className, $type, $parameters) {
	\Log::info("Event $type for class $className has been fired with parameters ", $parameters);
};
\Supra\Event\Registry::listen('Project\Rss\Controller', 'index', $listenerFunction);
