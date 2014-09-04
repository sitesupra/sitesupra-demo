<?php

namespace Supra\Core\Event;

interface RequestResponseListenerInterface
{
	/**
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event);
}