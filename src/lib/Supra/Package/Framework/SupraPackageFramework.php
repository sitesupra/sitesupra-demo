<?php

namespace Supra\Package\Framework;

use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\AssetFactory;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Package\PackageLocator;
use Supra\Package\Cms\Twig\CmsExtension;
use Supra\Package\Framework\Command\AssetsPublishCommand;
use Supra\Package\Framework\Command\ContainerDumpCommand;
use Supra\Package\Framework\Command\ContainerPackagesListCommand;
use Supra\Package\Framework\Command\DoctrineSchemaUpdateCommand;
use Supra\Package\Framework\Command\RoutingListCommand;
use Supra\Package\Framework\Command\SupraShellCommand;
use Supra\Package\Framework\Listener\NotFoundAssetExceptionListener;
use Supra\Package\Framework\Twig\FrameworkExtension;

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
		$container->getConsole()->add(new DoctrineSchemaUpdateCommand());

		//include supra helpers
		$cmsExtension = new CmsExtension();
		$cmsExtension->setContainer($container);
		$container->getTemplating()->addExtension($cmsExtension);

		$container[$this->name.'.twig_extension'] = function () {
			return new FrameworkExtension();
		};

		$container->getTemplating()->addExtension($container[$this->name.'.twig_extension']);

		//routing
		$container->getRouter()->loadConfiguration(
			PackageLocator::locateConfigFile($this, 'routes.yml')
		);

		//404 listener for less/css files and on-the-fly compilation
		$container[$this->name.'.not_found_asset_exception_listener'] = function () {
			return new NotFoundAssetExceptionListener();
		};

		$container->getEventDispatcher()->addListener(
			KernelEvent::ERROR404,
			array($container[$this->name.'.not_found_asset_exception_listener'], 'listen')
		);

		//configure and register assetic
		//$factory = new AssetFactory($container->getApplication()->getWebRoot());
		//$container->getTemplating()->addExtension(new AsseticExtension($factory));
	}

}
