<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(Container $container)
	{
		//routing
		$container->getRouter()->loadConfiguration(
				PackageLocator::locateConfigFile($this, 'routes.yml')
			);
	}

}
