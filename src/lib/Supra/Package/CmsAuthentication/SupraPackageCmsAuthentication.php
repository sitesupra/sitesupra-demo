<?php

namespace Supra\Package\CmsAuthentication;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationRequestListener;

class SupraPackageCmsAuthentication extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$container['cms_autherntication.request_listener'] = new CmsAuthenticationRequestListener();

		$container->getEventDispatcher()
			->addListener(KernelEvent::REQUEST, array($container['cms_autherntication.request_listener'], 'listen'));
	}

}
