<?php

namespace Supra\Component\Configuration;

use Supra\Configuration\ConfigurationInterfaceParserCallback;
use Supra\Configuration\Parser\ParserInterface;
use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Loader\Strategy\NamespaceLoaderStrategy;
use Supra\Loader\Loader;

/**
 * ComponentConfiguration
 *
 */
class ComponentConfiguration implements ConfigurationInterfaceParserCallback
{

	/**
	 * Parser instance
	 *
	 * @var ComponentConfigurationLoader
	 */
	protected $configLoader;

	/**
	 * Component ID
	 * 
	 * @var string
	 */
	public $componentId;


	public function setLoader($loader)
	{
		$this->configLoader = $loader;
	}
	
	/**
	 * @inheritdoc
	 */
	public function configure() 
	{
		$supraLoader = Loader::getInstance();
		$namespace = $this->componentId;
		$path = $this->configLoader->getFilePath();
		$path = dirname($path);
		$componentNamespace = new NamespaceLoaderStrategy($namespace, $path);
		$supraLoader->registerNamespace($componentNamespace);
	}
}
