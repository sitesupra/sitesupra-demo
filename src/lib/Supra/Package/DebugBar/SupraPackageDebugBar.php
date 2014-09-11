<?php

namespace Supra\Package\DebugBar;

use DebugBar\StandardDebugBar;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\DebugBar\Event\Listener\AssetsPublishEventListener;
use Supra\Package\DebugBar\Event\Listener\DebugBarResponseListener;
use Supra\Package\Framework\Event\FrameworkConsoleEvent;

class SupraPackageDebugBar extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$debugBar = new StandardDebugBar();

		$container[$this->name.'.debug_bar'] = $debugBar;

		$container[$this->name.'.response_listener'] = new DebugBarResponseListener();
		$container[$this->name.'.assets_listener'] = new AssetsPublishEventListener();

		$container->getEventDispatcher()
			->addListener(KernelEvent::RESPONSE, array($container[$this->name.'.response_listener'], 'listen'));

		$container->getEventDispatcher()
			->addListener(FrameworkConsoleEvent::ASSETS_PUBLISH, array($container[$this->name.'.assets_listener'], 'listen'));
	}

}