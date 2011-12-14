<?php

namespace Supra\BannerMachine\BannerType;

use Supra\BannerMachine\Entity\Banner;
use Supra\BannerMachine\Entity\ImageBanner;

abstract class BannerTypeAbstraction
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var integer
	 */
	protected $width;

	/**
	 * @var integer
	 */
	protected $height;

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param integer $width 
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}

	/**
	 * @param integer $height 
	 */
	public function setHeight($height)
	{
		$this->height = $height;
	}

	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 *
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param Banner $banner 
	 */
	public function validate(Banner $banner)
	{
		if ($banner instanceof ImageBanner) {
			$this->validateImageBanner($banner);
		}
	}
	
	abstract protected function validateImageBanner(ImageBanner $banner);

}

