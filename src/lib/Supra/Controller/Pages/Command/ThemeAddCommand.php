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
use Supra\Controller\Pages\Entity\Theme\Theme;
use Supra\Controller\Pages\Entity\Theme\ThemeLayout;
use Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder;
use Supra\Controller\Pages\Entity\Theme\ThemeParameterSet;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;
use Supra\Controller\Layout\Theme\ThemeProvider;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Layout\Exception;

class ThemeAddCommand extends Command
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
		$this->setName('su:themes:add')
				->setDescription('Add/update theme')
				->addArgument('name', Input\InputArgument::REQUIRED, 'Theme name')
				->addOption('directory', null, Input\InputArgument::OPTIONAL, 'Theme directory', null)
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

		$themeName = $input->getArgument('name');

		$themeDirectory = $input->getOption('directory');

		if ( ! empty($themeDirectory)) {
			
			$themeDirectory = realpath($themeDirectory);
			$themeConfigurationFilename = $themeDirectory . DIRECTORY_SEPARATOR . 'theme.yml';
		} else {

			$themeDirectory = $themeProvider->getRootDir() . DIRECTORY_SEPARATOR . $themeName;
			$themeConfigurationFilename = $themeProvider->getThemeConfigurationFilename($themeName);
		}

		if ( ! file_exists($themeConfigurationFilename)) {
			throw new Exception\RuntimeException('Theme configuration file "' . $themeConfigurationFilename . '" does not exist.');
		}

		$theme = $themeProvider->findThemeByName($themeName);
		
		if (empty($theme)) {
			/* @var $theme Theme */

			$theme = $themeProvider->makeNewTheme();
			$theme->setName($themeName);
			
			$defaultParameterSet = new ThemeParameterSet();
			$defaultParameterSet->setName('default');
			$theme->addParameterSet($defaultParameterSet);
		}

		$theme->setRootDir($themeDirectory);
		$theme->setUrlBase($themeProvider->getUrlBase() . DIRECTORY_SEPARATOR . $themeName);

		$theme->setConfigMd5(md5(file_get_contents($themeConfigurationFilename)));

		$yamlParser = new YamlParser();
		$configurationLoader = new ThemeConfigurationLoader();
		$configurationLoader->setParser($yamlParser);
		$configurationLoader->setTheme($theme);
		$configurationLoader->setCacheLevel(ThemeConfigurationLoader::CACHE_LEVEL_EXPIRE_BY_MODIFICATION);

		$configurationLoader->loadFile($themeConfigurationFilename);
		
		$themeProvider->storeTheme($theme);

		$output->writeln('Theme "' . $themeName . '" added/updated.');
	}

}
