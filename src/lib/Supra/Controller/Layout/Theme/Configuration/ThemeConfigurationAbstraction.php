<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\Entity\Theme;
use Supra\Configuration\Exception;
use Supra\Configuration\Loader\LoaderRequestingConfigurationInterface;
use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Controller\Layout\Theme\ThemeProvider;

abstract class ThemeConfigurationAbstraction implements ConfigurationInterface, LoaderRequestingConfigurationInterface
{

	/**
	 * @var ThemeConfigurationLoader
	 */
	protected $loader;

	/**
	 * @param ComponentConfigurationLoader $loader
	 * @throws Exception\RuntimeException 
	 */
	public function setLoader(ComponentConfigurationLoader $loader)
	{
		if ( ! $loader instanceof ThemeConfigurationLoader) {
			throw new Exception\RuntimeException('ThemeConfiguration must be used with ThemeConfigurationLoader.');
		}

		$this->loader = $loader;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme)
	{
		$this->loader->setTheme($theme);
	}

	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return $this->loader->getTheme();
	}

	/**
	 *  @return ThemeProvider
	 */
	protected function getThemeProvider()
	{
		return $this->loader->getThemeProvider();
	}

}
