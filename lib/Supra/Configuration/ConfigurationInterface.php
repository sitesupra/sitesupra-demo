<?php

namespace Supra\Configuration;

/**
 * Interface for all simple configuration objects
 */
interface ConfigurationInterface
{
	/**
	 * Configures the environment
	 * @return mixed
	 */
	public function configure();
}
