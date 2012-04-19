<?php

namespace Supra\Configuration\Loader;

use Supra\Configuration\ConfigurationInterface;

/**
 * Interface for all simple configuration objects
 */
interface LoaderRequestingConfigurationInterface extends ConfigurationInterface
{

	/**
	 * Sets loader
	 * @var ComponentConfigurationLoader $loader
	 */
	public function setLoader(ComponentConfigurationLoader $loader);
}
