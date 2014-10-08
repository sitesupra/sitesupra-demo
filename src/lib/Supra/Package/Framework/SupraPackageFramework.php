<?php

namespace Supra\Package\Framework;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Locale\Locale;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\Listener\LocaleDetectorListener;
use Supra\Package\Cms\Twig\CmsExtension;
use Supra\Package\Framework\Command\AssetsPublishCommand;
use Supra\Package\Framework\Command\ContainerDumpCommand;
use Supra\Package\Framework\Command\ContainerPackagesListCommand;
use Supra\Package\Framework\Command\DoctrineSchemaCreateCommand;
use Supra\Package\Framework\Command\DoctrineSchemaDropCommand;
use Supra\Package\Framework\Command\DoctrineSchemaUpdateCommand;
use Supra\Package\Framework\Command\RoutingListCommand;
use Supra\Package\Framework\Command\SupraShellCommand;
use Supra\Package\Framework\Listener\NotFoundAssetExceptionListener;
use Supra\Package\Framework\Twig\FrameworkExtension;

class SupraPackageFramework extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		//register commands
		$container->getConsole()->add(new ContainerDumpCommand());
		$container->getConsole()->add(new ContainerPackagesListCommand());
		$container->getConsole()->add(new RoutingListCommand());
		$container->getConsole()->add(new SupraShellCommand());
		$container->getConsole()->add(new AssetsPublishCommand());
		$container->getConsole()->add(new DoctrineSchemaUpdateCommand());
		$container->getConsole()->add(new DoctrineSchemaDropCommand());
		$container->getConsole()->add(new DoctrineSchemaCreateCommand());

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
			$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);

		//404 listener for less/css files and on-the-fly compilation
		$container[$this->name.'.not_found_asset_exception_listener'] = function () {
			return new NotFoundAssetExceptionListener();
		};

		$container->getEventDispatcher()->addListener(
			KernelEvent::ERROR404,
			array($container[$this->name.'.not_found_asset_exception_listener'], 'listen')
		);

		// Locale detection
		$container[$this->name.'.locale_detector_listener'] = function () {
			return new LocaleDetectorListener();
		};

		$container->getEventDispatcher()->addListener(
			// @FIXME: subscribe to controller pre-execute event instead.
			KernelEvent::REQUEST,
			array($container[$this->name.'.locale_detector_listener'], 'listen')
		);

		//configure and register assetic
		//$factory = new AssetFactory($container->getApplication()->getWebRoot());
		//$container->getTemplating()->addExtension(new AsseticExtension($factory));
	}

	public function finish(ContainerInterface $container)
	{
		$container->extend('locale.manager', function (LocaleManager $localeManager, ContainerInterface $container) {
			foreach ($container->getParameter('framework.locales') as $locale) {
				$localeObject = new Locale();
				$localeObject->setId($locale['id']);
				$localeObject->setTitle($locale['title']);
				$localeObject->setActive($locale['active']);
				$localeObject->setCountry($locale['country']);
				$localeObject->setProperties($locale['properties']);

				$localeManager->add($localeObject);
			}

			foreach ($container->getParameter('framework.locale_detectors') as $detector) {
				$localeManager->addDetector($container[$detector]);
			}

			foreach ($container->getParameter('framework.locale_storage') as $storage) {
				$localeManager->addStorage($container[$storage]);
			}

			$localeManager->setCurrent($container->getParameter('framework.current_locale'));

			return $localeManager;
		});
	}
}
