<?php

namespace Supra\Package\Cms\Pages\Listener;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Package\Cms\Pages\Request\PageRequestView;


class NotFoundExceptionListener implements RequestResponseListenerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event)
	{
		if ($event->hasResponse()) {
			// do nothing if response exists already
			return;
		}

		$request = $event->getRequest();

		$pageController = $this->container['cms.pages.controller'];
		/* @var $pageController \Supra\Package\Cms\Controller\PageController */

		$request->attributes->set('path', '404');
		
		$pageRequest = new PageRequestView($request);
		$pageRequest->setContainer($this->container);

		try {
			$event->setResponse(
					$pageController->execute($pageRequest)
			);
		} catch (ResourceNotFoundException $e) {} // ignore silently
	}
}
