<?php

namespace Supra\Core\Locale\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Core\Event\RequestResponseEvent;

/**
 * Locale detection PreFilter
 */
class LocaleDetectorListener implements RequestResponseListenerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function listen(RequestResponseEvent $event)
	{
		$this->container->getLocaleManager()
				->detect($this->container->getRequest());
	}

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}