<?php

namespace Supra\Core\Event;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TraceableEventDispatcher extends EventDispatcher implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	protected $eventTrace;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getEventTrace()
	{
		return $this->eventTrace;
	}

	public function dispatch($eventName, Event $event = null)
	{
		$this->logEvent($eventName, $event);

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

	protected function logEvent($name, $event)
	{
		$context = array();

		if ($event instanceof RequestResponseEvent) {
			$context['url'] = $event->getRequest()->getPathInfo();
		}

		$this->container->getLogger()->addDebug(sprintf('Processing %s event', $name), $context);
	}
}
