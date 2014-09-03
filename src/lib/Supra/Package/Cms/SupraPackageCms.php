<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		//@todo: move this to configuration
		$container->setParameter('cms.prefix', '/cms');

		//routing
		$container->getRouter()->loadConfiguration(
				PackageLocator::locateConfigFile($this, 'routes.yml')
			);
	}

}
