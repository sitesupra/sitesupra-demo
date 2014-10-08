<?php

namespace Supra\Core\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TraceableEventDispatcher extends EventDispatcher
{
	protected $eventTrace;

	public function getEventTrace()
	{
		return $this->eventTrace;
	}

	public function dispatch($eventName, Event $event = null)
	{
		$listeners = array();

		foreach ($this->getListeners($eventName) as $callable) {
			if ($callable instanceof \Closure) {
				$listeners[] = 'Closure';
			} elseif (is_array($callable) && count($callable) == 2) {
				$listeners[] = get_class($callable[0]);
			} else {
				$listeners[] = 'Unknown';
			}
		}

		$this->eventTrace[] = array(
			'name' => $eventName,
			'timestamp' => microtime(true),
			'listeners' => $listeners,
			'event' => $event
		);

		return parent::dispatch($eventName, $event);
	}
}
