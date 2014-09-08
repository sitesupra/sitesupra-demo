<?php

namespace Supra\Package\Framework;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Framework\Command\ContainerDumpCommand;
use Supra\Package\Framework\Command\ContainerPackagesListCommand;
use Supra\Package\Framework\Command\RoutingListCommand;
use Supra\Package\Framework\Command\SupraShellCommand;

class SupraPackageFramework extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$container->getConsole()->add(new ContainerDumpCommand());
		$container->getConsole()->add(new ContainerPackagesListCommand());
		$container->getConsole()->add(new RoutingListCommand());
		$container->getConsole()->add(new SupraShellCommand());
	}

}
