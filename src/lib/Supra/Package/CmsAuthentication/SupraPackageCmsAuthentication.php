<?php

namespace Supra\Package\CmsAuthentication;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationRequestListener;

class SupraPackageCmsAuthentication extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		$container[$this->name.'.request_listener'] = new CmsAuthenticationRequestListener();

		$container->getEventDispatcher()
			->addListener(KernelEvent::REQUEST, array($container[$this->name.'.request_listener'], 'listen'));

		//routing
		$container->getRouter()->loadConfiguration(
			PackageLocator::locateConfigFile($this, 'routes.yml')
		);
	}

}
