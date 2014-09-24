<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;
use Supra\Package\Cms\Application\CmsDashboardApplication;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		//routing
		$container->getRouter()->loadConfiguration(
				PackageLocator::locateConfigFile($this, 'routes.yml')
			);

		$container->getApplicationManager()->registerApplication(new CmsDashboardApplication());
	}

}
