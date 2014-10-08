<?php

namespace Supra\Package\DebugBar\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\ConsoleEvent;
use Supra\Core\Event\ConsoleEventListenerInterface;

class AssetsPublishEventListener implements ConsoleEventListenerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param ConsoleEvent $event
	 * @return mixed|void
	 */
	public function listen(ConsoleEvent $event)
	{
		$event->getOutput()->writeln('<info>DebugBar:</info> also publishing assets...');

		$reflection = new \ReflectionClass('\DebugBar\DebugBar');

		$debugBarRoot = dirname($reflection->getFileName());

		$eventData = $event->getData();

		$webRootPublic = $eventData['webRootPublic'];

		if (is_link($webRootPublic.'debugbar')) {
			unlink($webRootPublic.'debugbar');
		}

		symlink($debugBarRoot.'/Resources', $webRootPublic.'debugbar');
	}

}