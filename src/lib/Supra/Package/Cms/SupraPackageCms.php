<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\Detector\ParameterLocaleDetector;
use Supra\Package\Cms\Application\CmsDashboardApplication;
use Supra\Package\Cms\Application\CmsPagesApplication;
use Supra\Package\Cms\Pages\Application\PageApplicationManager;
use Supra\Package\Cms\Pages\Application\BlogPageApplication;
use Supra\Package\Cms\Pages\Application\GlossaryPageApplication;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultThemeProvider;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		//routing
		$container->getRouter()->loadConfiguration(
				$container->getApplication()->locateConfigFile($this, 'routes.yml')
			);

		$container->getApplicationManager()->registerApplication(new CmsDashboardApplication());
		$container->getApplicationManager()->registerApplication(new CmsPagesApplication());

		// Page Apps Manager
		$container[$this->name . '.page_application_manager'] = function () {

			$manager = new PageApplicationManager();
			
			$manager->registerApplication(new BlogPageApplication());
			$manager->registerApplication(new GlossaryPageApplication());

			return $manager;
		};

		// Theme Provider
		$container[$this->name . '.theme_provider'] = function () {
			return new DefaultThemeProvider();
		};
	}

	public function finish(ContainerInterface $container)
	{
		/// @FIXME: completely wrong. Doing this just to make the Pages to work.
		$container['locale.manager.cms'] = function ($container) {
			$localeManager = clone $container->getLocaleManager();
	
			$localeManager->processInactiveLocales();

			$localeManager->addDetector(new ParameterLocaleDetector());

			$localeManager->detect($container->getRequest());

			return $localeManager;
		};
	}
}
