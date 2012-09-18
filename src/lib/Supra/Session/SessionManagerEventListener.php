<?php

namespace Supra\Session;

use Supra\Controller\Event\FrontControllerShutdownEventArgs;

class SessionManagerEventListener
{

	protected $sessionManager;

	function __construct(SessionManager $sessionManager)
	{
		$this->sessionManager = $sessionManager;
	}

	public function frontControllerShutdownEvent(FrontControllerShutdownEventArgs $eventArgs)
	{
		$sessionManager = $this->sessionManager;

		if ($sessionManager->isStarted()) {
			$sessionHandler = $sessionManager->getHandler();
			$sessionHandler->close();
		}
	}

}
