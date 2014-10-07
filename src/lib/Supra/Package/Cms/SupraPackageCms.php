<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Detector\ParameterLocaleDetector;
use Supra\Package\Cms\Application\CmsDashboardApplication;
use Supra\Package\Cms\Application\CmsPagesApplication;
use Supra\Package\Cms\Pages\Application\PageApplicationManager;
use Supra\Package\Cms\Pages\Application\BlogPageApplication;
use Supra\Package\Cms\Pages\Application\GlossaryPageApplication;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultThemeProvider;
use Supra\Package\Cms\Pages\Listener\VersionedEntityRevisionSetterListener;
use Supra\Package\Cms\Pages\Listener\VersionedEntitySchemaListener;
use Supra\Package\Cms\Pages\Listener\TimestampableListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\PDOMySql;

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

		$this->injectDraftEntityManager($container);

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

		$frameworkConfiguration = $container->getApplication()->getConfigurationSection('framework');

		$frameworkConfiguration['doctrine']['event_managers']['public']['subscribers'][] = 'supra.cms.doctrine.event_subscriber.timestampable';
	}

	public function finish(ContainerInterface $container)
	{
		// Extended Locale Manager
		$container->extend('locale.manager', function (LocaleManager $localeManager, ContainerInterface $container) {

			$localeManager->processInactiveLocales();

			$localeManager->addDetector(new ParameterLocaleDetector());

			return $localeManager;
		});
	}

	/**
	 * @param ContainerInterface $container
	 */
	private function injectDraftEntityManager(ContainerInterface $container)
	{
		// separate EventManager
		$container['doctrine.event_manager.cms'] = function (ContainerInterface $container) {
			
			$eventManager = clone $container['doctrine.event_manager.public'];
			/* @var $eventManager \Doctrine\Common\EventManager */

			$eventManager->addEventSubscriber(new VersionedEntitySchemaListener());
			$eventManager->addEventSubscriber(new VersionedEntityRevisionSetterListener());

			// @TODO: quite rudimental stuff.
			// might be easily replaced with @HasLifecycleCallbacks + @prePersist + @preUpdate
			$eventManager->addEventSubscriber(new TimestampableListener());

			return $eventManager;
		};

		// separate connection. unfortunately.
		$container['doctrine.connections.cms'] = function (ContainerInterface $container) {

			// @TODO: clone somehow default connection?
			$connection = new Connection(
				array(
					'host' => 'localhost',
					'user' => 'root',
					'password' => 'root',
					'dbname' => 'supra9'
				),
				new PDOMySql\Driver(),
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.cms']
			);

			return $connection;
		};

		// entity manager
		$container['doctrine.entity_managers.cms'] = function (ContainerInterface $container) {
			return EntityManager::create(
				$container['doctrine.connections.cms'],
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.cms']
			);
		};
	}
}
