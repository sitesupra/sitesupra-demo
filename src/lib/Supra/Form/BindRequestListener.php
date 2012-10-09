<?php

namespace Supra\Form;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Supra BindRequestListener
 */
class BindRequestListener implements EventSubscriberInterface
{

	public static function getSubscribedEvents()
	{
		// Must be called before Symfony preBind event
		return array(FormEvents::PRE_BIND => array('preBind', 129));
	}

	/**
	 * Converts Supra HTTP request into Symfony request object
	 * @param \Symfony\Component\Form\FormEvent $event
	 */
	public function preBind(FormEvent $event)
	{
		$request = $event->getData();

		// only if is supra HttpRequest
		if ( ! $request instanceof \Supra\Request\HttpRequest) {
			return;
		}

		/* @var $request \Supra\Request\HttpRequest */
		$symfonyRequest = new Request(
				$request->getQuery()->getArrayCopy(),
				$request->getPost()->getArrayCopy(),
				array(),
				$request->getCookies(),
				$request->getPostFiles()->getArrayCopy(),
				$request->getServer());

		$event->setData($symfonyRequest);
	}

}
