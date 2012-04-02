<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;

class ThemeParameterConfiguration implements ConfigurationInterface
{

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $defaultValue;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var string
	 */
	public $groupName;

	public function configure()
	{
		
	}

}
