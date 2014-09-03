<?php

namespace Supra\Package\Framework;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Framework\Command\RoutingListCommand;

class SupraPackageFramework extends AbstractSupraPackage
{
	public function inject(Container $container)
	{
		$container->getConsole()->add(new RoutingListCommand());
	}

}
