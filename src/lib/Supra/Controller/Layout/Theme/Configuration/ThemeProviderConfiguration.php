<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;

class ThemeProviderConfiguration implements ConfigurationInterface
{

	/**
	 * @var boolean
	 */
	public $isDefault;

	/**
	 * @var string
	 */
	public $class;

	/**
	 * @var string
	 */
	public $rootDirectory;

	public function configure()
	{
		$provider = new $this->class();

		$directory = SUPRA_PATH . DIRECTORY_SEPARATOR . $this->rootDirectory;

		if ( ! file_exists($directory)) {
			throw new Exception\RuntimeException('Theme provider root directory "' . $directory . '" does not exist.');
		}

		$provider->setRootDir($directory);

		if ($this->isDefault) {
			ObjectRepository::setDefaultThemeProvider($provider);
		} else {
			ObjectRepository::setThemeProvider($this->namespace, $provider);
		}
	}

}
