<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\Loader\ComponentConfigurationLoader;

class BlockControllerConfigurationProxy
{
	/**
	 * @var string
	 */
	protected $controllerName;
	
	/**
	 * @var string
	 */
	protected $configurationClass;
	
	/**
	 * @var mixed
	 */
	protected $configurationValues;
	
	/**
	 * @var ComponentConfigurationLoader
	 */
	protected $loader;

	/**
	 * @param ComponentConfigurationLoader $loader
	 * @param mixed $configurationValues
	 */
	public function __construct(ComponentConfigurationLoader $loader, $configurationClass, $configurationValues)
	{
		$this->loader = $loader;
		$this->configurationClass = $configurationClass;
		$this->configurationValues = $configurationValues;
		
		// @TODO: looks not nice
		$this->controllerName = $configurationValues['class'];
	}
	
	/**
	 * 
	 * @return BlockControllerConfiguration
	 */
	public function load()
	{
		return $this->loader->processObject(
				$this->configurationClass, 
				$this->configurationValues
		);
	}
	
	public function getControllerName()
	{
		return $this->controllerName;
	}
	
}