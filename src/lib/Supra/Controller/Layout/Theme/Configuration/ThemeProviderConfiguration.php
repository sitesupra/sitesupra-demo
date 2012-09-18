<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\Loader\WriteableIniConfigurationLoader;

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
	public $rootDir;

	/**
	 * @var string
	 */
	public $urlBase;

	/**
	 * @var string
	 */
	public $namespace;

	public function configure()
	{
		$provider = new $this->class();

		$directory = SUPRA_PATH . DIRECTORY_SEPARATOR . $this->rootDir;

		if ( ! file_exists($directory)) {
			throw new Exception\RuntimeException('Theme provider root directory "' . $directory . '" does not exist.');
		}

		$provider->setRootDir($directory);
		$provider->setUrlBase($this->urlBase);
		
		$writeableIniLoader = new WriteableIniConfigurationLoader('theme.ini');
		ObjectRepository::setIniConfigurationLoader($provider, $writeableIniLoader);
	
		if ($this->isDefault) {
			ObjectRepository::setDefaultThemeProvider($provider);
		} else {
			ObjectRepository::setThemeProvider($this->namespace, $provider);
		}
	}

}
