<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Templating\TwigTemplating;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Detector\ParameterDetector;
use Supra\Package\Cms\Command\LoadFixturesCommand;
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
use Supra\Package\Cms\Pages\Block\BlockCollection;
use Supra\Package\Cms\Pages\Block\BlockGroupConfiguration;
use Supra\Package\Cms\Pages\Twig\PageExtension;
use Supra\Package\Cms\Pages\Layout\Processor\TwigProcessor;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		//routing
		$container->getRouter()->loadConfiguration(
			$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);

		$container->getConsole()->add(new LoadFixturesCommand());

		$container->getApplicationManager()->registerApplication(new CmsDashboardApplication());
		$container->getApplicationManager()->registerApplication(new CmsPagesApplication());
		$container->getApplicationManager()->registerApplication(new CmsInternalUserManagerApplication());
		$container->getApplicationManager()->registerApplication(new CmsMediaLibraryApplication());

		// Page Apps Manager
		$container[$this->name . '.pages.page_application_manager'] = function () {
			return new PageApplicationManager();
		};

		//setting up doctrine
		$frameworkConfiguration = $container->getApplication()->getConfigurationSection('framework');

		//add audited entities
		$frameworkConfiguration['doctrine_audit']['entities'] = array_merge(
			$frameworkConfiguration['doctrine_audit']['entities'],
			array(
				'Supra\Package\Cms\Entity\Abstraction\Localization',
				'Supra\Package\Cms\Entity\Abstraction\AbstractPage',
				'Supra\Package\Cms\Entity\Page',
				'Supra\Package\Cms\Entity\GroupPage',
				'Supra\Package\Cms\Entity\Template',
				'Supra\Package\Cms\Entity\PageLocalization',
				'Supra\Package\Cms\Entity\PageLocalizationPath',
				'Supra\Package\Cms\Entity\TemplateLocalization',
				'Supra\Package\Cms\Entity\Abstraction\Block',
				'Supra\Package\Cms\Entity\Abstraction\PlaceHolder',
				'Supra\Package\Cms\Entity\PagePlaceHolder',
				'Supra\Package\Cms\Entity\TemplatePlaceHolder',
				'Supra\Package\Cms\Entity\PageBlock',
				'Supra\Package\Cms\Entity\TemplateBlock',
				'Supra\Package\Cms\Entity\BlockProperty',
				'Supra\Package\Cms\Entity\BlockPropertyMetadata',
				'Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement',
				'Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement',
				'Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract',
			)
		);

		$frameworkConfiguration['doctrine']['event_managers']['public'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.file_storage.event_subscriber.file_path_change_listener',
					'supra.cms.pages.event_subscriber.page_path_generator',
					'supra.cms.pages.event_subscriber.image_size_creator_listener',
				)
			)
		);

		/*$frameworkConfiguration['doctrine']['event_managers']['cms'] = array_merge_recursive(
			$frameworkConfiguration['doctrine']['event_managers']['public'],
			array(
				'subscribers' => array(
					'supra.cms.doctrine.event_subscriber.versioned_entity_schema',
					'supra.cms.doctrine.event_subscriber.versioned_entity_revision_setter',
					'supra.cms.pages.event_subscriber.page_path_generator',
					'supra.cms.pages.event_subscriber.image_size_creator_listener',
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

		// Twig Extension
		$container->getTemplating()->addExtension(new PageExtension());

		//event listeners
		$container[$this->getName().'.cms_exception_listener'] = function () {
			return new CmsExceptionListener();
		};

		$container[$this->getName().'.pages.not_found_exception_listener'] = function () {
			return new Pages\Listener\NotFoundExceptionListener();
		};

		$container->getEventDispatcher()->addListener(
			KernelEvent::ERROR404,
			array($container[$this->getName().'.pages.not_found_exception_listener'], 'listen')
		);

		$container->getEventDispatcher()->addListener(
			KernelEvent::EXCEPTION,
			array($container[$this->getName().'.cms_exception_listener'], 'listen')
		);

		$container[$this->getName() . '.pages.layout_processor'] = function ($container) {

			$templating = $container->getTemplating();

			if (! $templating instanceof TwigTemplating) {
				throw new \RuntimeException('Twig layout processor requires twig templating engine.');
			}

			return new TwigProcessor($templating->getTwig());
		};

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
