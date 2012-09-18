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
use Doctrine\ORM\EntityManager;
use Supra\Controller\Layout\Exception;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;

class ThemeRemoveCommand extends Command
{

	/**
	 * @var string
	 */
	protected $themeProviderNamespace;

	/**
	 * @var ThemeProviderAbstraction
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
	public function setThemeProvider(ThemeProvider $themeProvider)
	{
		$this->themeProvider = $themeProvider;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName('su:themes:remove')
				->setDescription('Remove theme')
				->addArgument('name', Input\InputArgument::REQUIRED, 'Theme name')
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

		$themeName = $input->getArgument('name');

		$themeProvider = $this->getThemeProvider();

		$activeTheme = $themeProvider->getActiveTheme();

		if ($activeTheme->getName() == $themeName) {
			throw new Exception\RuntimeException('Can not remove theme "' . $themeName . '" because it is currently active.');
		}

		$theme = $themeProvider->findThemeByName($themeName);

		if (empty($theme)) {
			$output->writeln('Theme "' . $themeName . '" not found.');
		} else {
			$themeProvider->removeTheme($theme);
			$output->writeln('Theme "' . $themeName . '" removed.');
		}
	}

}
