<?php

namespace Supra\BannerMachine\Configuration\BannerType;

use Supra\Configuration\ConfigurationInterface;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;

abstract class BannerTypeConfigurationAbstraction implements ConfigurationInterface
{

	public $id;
	public $width;
	public $height;
	public $name;

	/**
	 * @var BannerTypeAbstraction
	 */
	protected $type;

	public function configure()
	{
		$this->makeType();
		
		if (empty($this->type)) {
			throw new Exception\ConfigurationException('type (in makeType()) must be created before configuration');
		}

		$this->type->setId($this->id);
		$this->type->setName($this->name);
		$this->type->setWidth($this->width);
		$this->type->setHeight($this->height);
	}
	
	abstract protected function makeType();

	/**
	 * @return BannerTypeAbstraction
	 */
	public function getType()
	{
		return $this->type;
	}

}

