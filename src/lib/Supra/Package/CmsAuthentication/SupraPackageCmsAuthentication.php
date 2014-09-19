<?php

namespace Supra\Package\CmsAuthentication;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;
use Supra\Package\CmsAuthentication\Application\CmsAuthenticationApplication;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationRequestListener;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationResponseListener;

class SupraPackageCmsAuthentication extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		$container[$this->name.'.request_listener'] = function () {
			return new CmsAuthenticationRequestListener();
		};

		$container[$this->name.'.response_listener'] = function () {
			return new CmsAuthenticationResponseListener();
		};

		$container->getEventDispatcher()
			->addListener(KernelEvent::REQUEST, array($container[$this->name.'.request_listener'], 'listen'));
		$container->getEventDispatcher()
			->addListener(KernelEvent::RESPONSE, array($container[$this->name.'.response_listener'], 'listen'));

		//routing
		$container->getRouter()->loadConfiguration(
			PackageLocator::locateConfigFile($this, 'routes.yml')
		);

		//applications
		$container->getApplicationManager()->registerApplication(new CmsAuthenticationApplication());
	}

}
