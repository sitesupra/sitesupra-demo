<?php

namespace Supra\Event;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\EventSubscriber;

/**
 * Binds event to the event manager
 */
class EventConfiguration implements ConfigurationInterface
{
	/**
	 * Class name of the listener
	 * @var string
	 */
	public $listener;
	
	/**
	 * List of subscribed events
	 * @var array
	 */
	public $events = array();
	
	public function configure()
	{
		$events = (array) $this->events;
		
		//TODO: maybe should have piossibility to bind without creating an instance
		$listener = Loader::getClassInstance($this->listener);
		
		if (empty($events) && $listener instanceof EventSubscriber) {
			$events = $listener->getSubscribedEvents();
		}
		
		//TODO: Can bind to the default event manager for now
		$eventManager = ObjectRepository::getEventManager('');
		$eventManager->listen($events, $listener);
	}
}
