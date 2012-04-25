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
	 * Component class
	 * @var string
	 */
	public $class;

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
		if (empty($this->id)) {
			$this->id = $this->class;
		} elseif (empty($this->class)) {
			$this->class = $this->id;
		}
		
		ObjectRepository::setComponentConfiguration($this->class, $this);
	}
}
