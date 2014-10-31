<?php

namespace Supra\Package\Cms\Listener;

use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;

class CmsExceptionListener implements RequestResponseListenerInterface
{
	/**
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event)
	{
		$exception = $event->getData();

		if ($exception instanceof CmsException) {
			$response = new SupraJsonResponse();
			$response->setErrorMessage($exception->getMessageKey() ? '{#'.$exception->getMessageKey().'#}' : $exception->getMessage());

			$event->setResponse($response);
		}
	}
}


