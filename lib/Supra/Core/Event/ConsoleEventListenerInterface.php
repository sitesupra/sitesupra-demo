<?php

namespace Supra\Core\Event;


interface ConsoleEventListenerInterface
{
	/**
	 * @param ConsoleEvent $event
	 * @return mixed
	 */
	public function listen(ConsoleEvent $event);
}