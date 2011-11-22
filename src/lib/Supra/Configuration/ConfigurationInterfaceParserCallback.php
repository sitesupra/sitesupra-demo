<?php

namespace Supra\Configuration;

use Supra\Configuration\Loader\ComponentConfigurationLoader;

/**
 * Interface for all simple configuration objects
 */
interface ConfigurationInterfaceParserCallback extends ConfigurationInterface
{

	/**
	 * Sets parser
	 * 
	 * @var ComponentConfigurationLoader $loader
	 */
	public function setLoader($loader);
	
}
