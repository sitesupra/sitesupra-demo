<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Detector\ParameterDetector;
use Supra\Package\Cms\FileStorage\Validation\ExtensionUploadFilter;
use Supra\Package\Cms\Application\CmsDashboardApplication;
use Supra\Package\Cms\Application\CmsInternalUserManagerApplication;
use Supra\Package\Cms\Application\CmsMediaLibraryApplication;
use Supra\Package\Cms\Application\CmsPagesApplication;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\FileStorage\ImageProcessor\Adapter\ImageMagickAdapter;
use Supra\Package\Cms\FileStorage\Validation\ExistingFileNameUploadFilter;
use Supra\Package\Cms\FileStorage\Validation\FileNameUploadFilter;
use Supra\Package\Cms\FileStorage\Validation\ImageSizeUploadFilter;
use Supra\Package\Cms\Listener\CmsExceptionListener;
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

		//add audited entities
		$frameworkConfiguration['doctrine_audit']['entities'] = array_merge(
			$frameworkConfiguration['doctrine_audit']['entities'],
			array(
				'Supra\Package\Cms\Entity\Page',
				'Supra\Package\CmsAuthentication\Entity\AbstractUser',
				'Supra\Package\CmsAuthentication\Entity\User'
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

		/*$frameworkConfiguration['doctrine']['event_managers']['cms'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.doctrine.event_subscriber.page_path_generator',
					'supra.cms.doctrine.event_subscriber.versioned_entity_schema',
					'supra.cms.doctrine.event_subscriber.versioned_entity_revision_setter'
				)
			)
		);

		$frameworkConfiguration['doctrine']['event_managers']['audit'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.doctrine.event_subscriber.audit_schema',
					'supra.cms.doctrine.event_subscriber.audit',
					'supra.cms.doctrine.event_subscriber.audit_manager',
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

		$frameworkConfiguration['doctrine']['connections']['audit'] = array_merge(
			$frameworkConfiguration['doctrine']['connections']['default'],
			array(
				'event_manager' => 'audit',
				'configuration' => 'audit'
			)
		);

		$frameworkConfiguration['doctrine']['configurations']['cms'] = array_merge(
			$frameworkConfiguration['doctrine']['configurations']['default'],
			array()
		);

		$frameworkConfiguration['doctrine']['configurations']['audit'] = array_merge(
			$frameworkConfiguration['doctrine']['configurations']['default'],
			array()
		);

		$frameworkConfiguration['doctrine']['entity_managers']['cms'] = array(
			'connection'	=> 'cms',
			'event_manager'	=> 'cms',
			'configuration'	=> 'cms'
		);

		$frameworkConfiguration['doctrine']['entity_managers']['audit'] = array(
			'connection'	=> 'audit',
			'event_manager'	=> 'audit',
			'configuration'	=> 'audit'
		);*/

		$container->getApplication()->setConfigurationSection('framework', $frameworkConfiguration);

		// Block collection
		$container[$this->name . '.pages.blocks.collection'] = function () {

			return new BlockCollection(array(
				new BlockGroupConfiguration('features', 'Features', true),
				new BlockGroupConfiguration('system', 'System'),
			));
		};

		//event listeners
		$container[$this->getName().'.cms_exception_listener'] = function () {
			return new CmsExceptionListener();
		};

		$container->getEventDispatcher()->addListener(
			KernelEvent::EXCEPTION,
			array($container[$this->getName().'.cms_exception_listener'], 'listen')
		);

		//the mighty file storage
		//todo: move to config.yml
		$container['cms.file_storage'] = function () {
			$storage = new FileStorage();

			$extensionFilter = new ExtensionUploadFilter();
			$extensionFilter->setMode(ExtensionUploadFilter::MODE_BLACKLIST);
			$extensionFilter->addItems(
				array('php', 'phtml', 'php3', 'php4', 'js', 'shtml',
					'pl' ,'py', 'cgi', 'sh', 'asp', 'exe', 'bat', 'jar', 'phar'
				));

			$fileNameFilter = new FileNameUploadFilter();

			$existingFileNameFilter = new ExistingFileNameUploadFilter();
			$imageSizeFilter =  new ImageSizeUploadFilter();

			$storage->addFileUploadFilter($extensionFilter);
			$storage->addFileUploadFilter($fileNameFilter);
			$storage->addFileUploadFilter($existingFileNameFilter);

			$storage->addFileUploadFilter($imageSizeFilter);

			$storage->addFolderUploadFilter($fileNameFilter);
			$storage->addFolderUploadFilter($existingFileNameFilter);

			$imageProcessorAdapter = new ImageMagickAdapter();
			$imageProcessorAdapter->setFileStorage($storage);
			$storage->setImageProcessorAdapter($imageProcessorAdapter);

			return $storage;
		};

		//media library constants
		//todo: move to config.yml
		$container->setParameter($this->getName().'.media_library_known_file_extensions',
			array('pdf', 'xls', 'xlsx', 'doc', 'docx', 'swf'));

		$container->setParameter($this->getName().'.media_library_check_file_existence', 'full');

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
