<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Task\LayoutProcessorTask;
use Supra\Controller\Layout\Exception\LayoutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Configuration\Parser\YamlParser;
use Supra\Controller\Pages\Entity\Theme;
use Supra\Controller\Pages\Entity\ThemeLayout;
use Supra\Controller\Pages\Entity\ThemeLayoutPlaceholder;
use Supra\Controller\Pages\Entity\ThemeParameterSet;
use Supra\Controller\Pages\Entity\ThemeParameter;
use Supra\Controller\Layout\Theme\ThemeProvider;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Layout\Exception;
use Supra\Controller\Pages\Entity\TemplateLayout;

class UpgradeTemplateLayoutsCommand extends Command
{

	/**
	 * @var string
	 */
	protected $themeProviderNamespace;

	/**
	 * @var ThemeProvider
	 */
	protected $themeProvider;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return string
	 */
	public function getThemeProviderNamespace()
	{
		if (empty($this->themeProviderNamespace)) {
			$this->themeProviderNamespace = $this;
		}

		return $this->themeProviderNamespace;
	}

	/**
	 * @param string $themeProviderNamespace 
	 */
	public function setThemeProviderNamespace($themeProviderNamespace)
	{
		$this->themeProviderNamespace = $themeProviderNamespace;
	}

	/**
	 * @return ThemeProviderAbstraction
	 */
	public function getThemeProvider()
	{
		if (empty($this->themeProvider)) {

			$em = $this->getEntityManager();

			$themeProvider = ObjectRepository::getThemeProvider($this->getThemeProviderNamespace());

			$themeProvider->setEntityManager($em);

			$this->themeProvider = $themeProvider;
		}

		return $this->themeProvider;
	}

	/**
	 * @param ThemeProviderAbstraction $themeProvider 
	 */
	public function setThemeProvider(ThemeProviderAbstraction $themeProvider)
	{
		$this->themeProvider = $themeProvider;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName('su:pages:upgrade_layouts')
				->setDescription('Upgrade template layouts to support themes.')
				->addOption('theme', null, Input\InputArgument::OPTIONAL, 'Theme name', 'default')
				->addOption('provider', null, Input\InputArgument::OPTIONAL, 'Theme provider namespance', null);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$providerNamespace = $input->getOption('provider');
		$this->setThemeProviderNamespace($providerNamespace);

		$themeProvider = $this->getThemeProvider();
		
		$themeName = $input->getOption('provider');

		/* @var $theme Theme */
		$theme = $themeProvider->findThemeByName($themeName);

		$em = $this->getEntityManager();

		$templateLayoutRepository = $em->getRepository(TemplateLayout::CN());

		$templateLayouts = $templateLayoutRepository->findAll();

		$themeLayouts = $theme->getLayouts();

		$themeLayoutFilenameNameMap = array();

		/* @var $themeLayout ThemeLayout */
		foreach ($themeLayouts as $themeLayout) {
			$themeLayoutFilenameNameMap[$themeLayout->getFilename()] = $themeLayout->getName();
		}

		foreach ($templateLayouts as $templateLayout) {
			/* @var $templateLayout TemplateLayout */

			$oldLayout = $templateLayout->getOldLayout();

			$oldLayoutFilename = $oldLayout->getFile();

			$layoutName = $themeLayoutFilenameNameMap[$oldLayoutFilename];

			$templateLayout->setLayoutName($layoutName);

			$em->persist($templateLayout);
		}

		$em->flush();

		$output->writeln('Upgrade complete.');
	}

}
