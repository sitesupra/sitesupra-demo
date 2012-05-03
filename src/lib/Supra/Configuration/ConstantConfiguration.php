<?php

namespace Supra\Configuration;

/**
 * Constant configuration class
 */
class ConstantConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $value;

	public function configure()
	{
		if ( ! defined($this->name)) {
			define($this->name, $this->value);
		}
	}

}
