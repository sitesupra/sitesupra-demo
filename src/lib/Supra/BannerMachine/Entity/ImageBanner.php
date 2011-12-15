<?php

namespace Supra\BannerMachine\Entity;

use Supra\FileStorage\Entity\Image as ImageFile;
use Supra\Html\HtmlTag;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerMachineController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\BannerMachine\BannerMachineRedirector;

/**
 * @Entity
 */
class ImageBanner extends FileBanner
{

	/**
	 * @return ImageFile
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param ImageFile $file 
	 */
	public function setFile(ImageFile $file)
	{
		parent::setFile($file);
	}

	/**
	 * @param BannerMachineController $controller
	 * @return string
	 */
	public function getExposureModeContent(BannerMachineController $controller)
	{
		$bannerProvider = ObjectRepository::getBannerProvider($this);
		
		$imgTag = $this->getImgTag();
		
		$hrefTag = new HtmlTag('a', $imgTag->toHtml());

		$redirectorParams = array(
				BannerMachineRedirector::REQUEST_KEY_BANNER_ID => $this->getId(),
				BannerMachineRedirector::REQUEST_KEY_EXTRA => $controller->getPropertyValue(BannerMachineController::PROPERTY_NAME_APPEND_TO_URL),
				BannerMachineRedirector::REQUEST_KEY_PAGE_REF => $controller->getPage()->getId()
		);

		$redirectorUrl = $bannerProvider->getRedirectorPath() . '?' . http_build_query($redirectorParams);

		$hrefTag->setAttribute('href', $redirectorUrl);

		return $hrefTag->toHtml();
	}
	
	/**
	 * @param BannerMachineController $controller
	 * @return string
	 */
	public function getEditModeContent(BannerMachineController $controller)
	{
		return $this->getImgTag()->toHtml();
	}

	/**
	 * @return HtmlTag
	 */
	protected function getImgTag()
	{
		$fileStorage = ObjectRepository::getFileStorage($this);

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerType = $bannerProvider->getType($this->getTypeId());

		$imgTag = new HtmlTag('img');
		$imgTag->setAttribute('src', $fileStorage->getWebPath($this->file));
		$imgTag->setAttribute('width', $bannerType->getWidth());
		$imgTag->setAttribute('height', $bannerType->getHeight());

		return $imgTag;
	}

	public function validate()
	{
		return true;
	}

}
