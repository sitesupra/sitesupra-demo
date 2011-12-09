<?php

namespace Supra\BannerMachine\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\BannerMachine\SizeType;

class SizeTypeConfiguration implements ConfigurationInterface
{

	public $id;
	public $width;
	public $height;
	public $name;

	/**
	 * @var SizeType
	 */
	protected $sizeType;

	public function configure()
	{
		$this->sizeType = new SizeType();

		$this->sizeType->setId($this->id);
		$this->sizeType->setName($this->name);
		$this->sizeType->setWidth($this->width);
		$this->sizeType->setHeight($this->height);
	}

	/**
	 * @return SizeType
	 */
	public function getSizeType()
	{
		return $this->sizeType;
	}

}
