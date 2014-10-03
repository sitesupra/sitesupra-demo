<?php

namespace Supra\Core\Locale\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Locale\Exception\LocaleException;

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
		try {
			$this->container->getLocaleManager()
					->detect($this->container->getRequest());
		} catch (LocaleException $e) {
		}
	}

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}