<?php

namespace Supra\BannerMachine\BannerType;

use Supra\BannerMachine\Entity\Banner;
use Supra\BannerMachine\Entity\ImageBanner;
use Supra\FileStorage\Entity\Image as ImageFile;

class DefaultSizeType extends BannerTypeAbstraction
{
	/**
	 * @var float
	 */
	protected $ratioDelta;

	/**
	 * @param float $ratioDelta 
	 */
	public function setRatioDelta($ratioDelta)
	{
		$this->ratioDelta = $ratioDelta;
	}

	/**
	 * @return float
	 */
	public function getRatioDelta()
	{
		return $this->ratioDelta;
	}

	protected function validateImageBanner(ImageBanner $banner)
	{
		/** @var $imageFile ImageFile */
		$imageFile = $banner->getFile();

		$imageWidth = $imageFile->getWidth();
		$imageHeight = $imageFile->getHeight();

		$imageRatio = $imageWidth / $imageHeight;

		$typeRatio = $this->getWidth() / $this->getHeight();
		
		if (
				($imageRatio > $typeRatio + $this->ratioDelta) ||
				($imageRatio < $typeRatio - $this->ratioDelta)
		) {
			throw new Exception\RuntimeException('Width / height ratio not valid for chosen image.');
		}
		
		if(
				($imageWidth > $this->getWidth() * (1 + $this->ratioDelta)) || 
				($imageWidth < $this->getWidth() * (1 - $this->ratioDelta)) ||
				($imageHeight > $this->getHeight() * (1 + $this->ratioDelta)) || 
				($imageHeight < $this->getHeight() * (1 - $this->ratioDelta))
		) { 
			throw new Exception\RuntimeException('Image size is not valid.');
		}
						
		return true;
	}

}

