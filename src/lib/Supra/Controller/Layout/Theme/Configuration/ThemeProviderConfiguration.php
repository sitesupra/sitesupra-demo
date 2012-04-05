<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;

class ThemeProviderConfiguration implements ConfigurationInterface
{

	public $isDefault;
	public $class;
	public $themes;
	public $assetsPath;

	public function configure()
	{
		$provider = new $this->class();
		/* @var $provider ThemeProviderAbstraction */

		if ($this->isDefault) {
			ObjectRepository::setDefaultThemeProvider($provider);
		} else {
			ObjectRepository::setThemeProvider($this->namespace, $provider);
		}

		foreach ($this->themes as $themeConfiguration) {

			/* @var $themeConfiguration ThemeConfiguration */

			$themeConfiguration->configure();

			$theme = $themeConfiguration->getTheme();
			
			$theme->setAssetsPath($this->assetsPath);

			$provider->addTheme($theme);
		}
	}

}
