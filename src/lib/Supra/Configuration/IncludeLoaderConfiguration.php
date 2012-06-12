<?php

namespace Supra\Configuration;

/**
 * Load static file configuration
 */
class IncludeLoaderConfiguration extends IncludeConfiguration
		implements Loader\LoaderRequestingConfigurationInterface
{
	/**
	 * @var Loader\ComponentConfigurationLoader
	 */
	protected $loader;
	
	/**
	 * @param Loader\ComponentConfigurationLoader $loader
	 */
	public function setLoader(Loader\ComponentConfigurationLoader $loader)
	{
		$this->loader = $loader;
	}
	
	/**
	 * @param string $file
	 */
	protected function parseFile($file)
	{
		// TODO: the classname might be configurable
		$loader = clone($this->loader);
		$loader->loadFile($file);
	}
}
