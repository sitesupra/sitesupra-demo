<?php

namespace Supra\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;

/**
 * ComponentConfiguration
 *
 */
class ComponentConfiguration implements ConfigurationInterface
{

	/**
	 * Component ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Component title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Component version
	 *
	 * @var string 
	 */
	public $version;

	/**
	 * @inheritdoc
	 */
	public function configure() 
	{
		ObjectRepository::setComponentConfiguration($this->id, $this);
	}
}
