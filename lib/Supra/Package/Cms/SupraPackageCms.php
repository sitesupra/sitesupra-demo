<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Detector\ParameterDetector;
use Supra\Package\Cms\Application\CmsDashboardApplication;
use Supra\Package\Cms\Application\CmsInternalUserManagerApplication;
use Supra\Package\Cms\Application\CmsMediaLibraryApplication;
use Supra\Package\Cms\Application\CmsPagesApplication;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Pages\Application\PageApplicationManager;
use Supra\Package\Cms\Pages\Application\BlogPageApplication;
use Supra\Package\Cms\Pages\Application\GlossaryPageApplication;
use Supra\Package\Cms\Pages\Block\BlockCollection;
use Supra\Package\Cms\Pages\Block\BlockGroupConfiguration;

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
		$container->getApplicationManager()->registerApplication(new CmsInternalUserManagerApplication());
		$container->getApplicationManager()->registerApplication(new CmsMediaLibraryApplication());

		// Page Apps Manager
		$container[$this->name . '.pages.page_application_manager'] = function () {

			$manager = new PageApplicationManager();
			
			$manager->registerApplication(new BlogPageApplication());
			$manager->registerApplication(new GlossaryPageApplication());

			return $manager;
		};

		//setting up doctrine
		$frameworkConfiguration = $container->getApplication()->getConfigurationSection('framework');

		$frameworkConfiguration['doctrine']['event_managers']['cms'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.doctrine.event_subscriber.page_path_generator',
					'supra.cms.doctrine.event_subscriber.versioned_entity_schema',
					'supra.cms.doctrine.event_subscriber.versioned_entity_revision_setter'
				)
			)
		);

		$frameworkConfiguration['doctrine']['event_managers']['public'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.file_storage.event_subscriber.file_path_change_listener'
				)
			)
		);

		$frameworkConfiguration['doctrine']['connections']['cms'] = array_merge(
			$frameworkConfiguration['doctrine']['connections']['default'],
			array(
				'event_manager' => 'cms',
				'configuration' => 'cms'
			)
		);

		$frameworkConfiguration['doctrine']['configurations']['cms'] = array_merge(
			$frameworkConfiguration['doctrine']['configurations']['default'],
			array()
		);

		$frameworkConfiguration['doctrine']['entity_managers']['cms'] = array(
			'connection'	=> 'cms',
			'event_manager'	=> 'cms',
			'configuration'	=> 'cms'
		);

		$container->getApplication()->setConfigurationSection('framework', $frameworkConfiguration);

		// Block collection
		$container[$this->name . '.pages.blocks.collection'] = function () {

			return new BlockCollection(array(
						new BlockGroupConfiguration('features', 'Features', true),
						new BlockGroupConfiguration('system', 'System'),
			));
		};

		//the mighty file storage
		$container['cms.file_storage'] = function () {
			return new FileStorage();
		};
	}

	public function finish(ContainerInterface $container)
	{
		// Extended Locale Manager
		$container->extend('locale.manager', function (LocaleManager $localeManager, ContainerInterface $container) {

			$localeManager->processInactiveLocales();

			$localeManager->addDetector(new ParameterDetector());

			return $localeManager;
		});
	}
}
