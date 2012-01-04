<?php

namespace Supra\BannerMachine\Configuration\BannerType;

use Supra\Configuration\ConfigurationInterface;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;

abstract class BannerTypeConfigurationAbstraction implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $id;
	
	/**
	 * @var int
	 */
	public $width;
	
	/**
	 * @var int
	 */
	public $height;
	
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var BannerTypeAbstraction
	 */
	protected $type;

	/**
	 * Main method
	 */
	public function configure()
	{
		$this->type = $this->makeType();
		
		$this->type->setId($this->id);
		$this->type->setName($this->name);
		$this->type->setWidth($this->width);
		$this->type->setHeight($this->height);
	}
	
	/**
	 * @return BannerTypeAbstraction
	 */
	abstract protected function makeType();

	/**
	 * @return BannerTypeAbstraction
	 */
	public function getType()
	{
		return $this->type;
	}

}

