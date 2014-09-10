<?php

namespace Supra\Package\Framework;

use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\AssetFactory;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Cms\Twig\CmsExtension;
use Supra\Package\Framework\Command\AssetsPublishCommand;
use Supra\Package\Framework\Command\ContainerDumpCommand;
use Supra\Package\Framework\Command\ContainerPackagesListCommand;
use Supra\Package\Framework\Command\RoutingListCommand;
use Supra\Package\Framework\Command\SupraShellCommand;

class SupraPackageFramework extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		//register commands
		$container->getConsole()->add(new ContainerDumpCommand());
		$container->getConsole()->add(new ContainerPackagesListCommand());
		$container->getConsole()->add(new RoutingListCommand());
		$container->getConsole()->add(new SupraShellCommand());
		$container->getConsole()->add(new AssetsPublishCommand());

		//include supra helpers
		$cmsExtension = new CmsExtension();
		$cmsExtension->setContainer($container);
		$container->getTemplating()->addExtension($cmsExtension);

		//configure and register assetic
		//$factory = new AssetFactory($container->getApplication()->getWebRoot());
		//$container->getTemplating()->addExtension(new AsseticExtension($factory));
	}

}
