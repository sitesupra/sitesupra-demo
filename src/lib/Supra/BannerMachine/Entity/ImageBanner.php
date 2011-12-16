<?php

namespace Supra\BannerMachine\Entity;

use Supra\FileStorage\Entity\Image as ImageFile;
use Supra\FileStorage\Entity\File as GenericFile;
use Supra\Html\HtmlTag;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerMachineController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\BannerMachine\BannerMachineRedirector;
use Supra\Cms\Exception\CmsException;

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
		if ( ! ($file instanceof ImageFile)) {
			throw new CmsException(null, 'Can not change type of banner file!');
		}

		parent::setFile($file);
	}

	/**
	 * @param BannerMachineController $controller
	 * @return string
	 */
	public function getExposureModeContent(BannerMachineController $controller)
	{
		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$redirectorParams = array(
				BannerMachineRedirector::REQUEST_KEY_BANNER_ID => $this->getId(),
				BannerMachineRedirector::REQUEST_KEY_EXTRA => $controller->getPropertyValue(BannerMachineController::PROPERTY_NAME_APPEND_TO_URL),
				BannerMachineRedirector::REQUEST_KEY_PAGE_REF => $controller->getPage()->getId()
		);

		$redirectorUrl = $bannerProvider->getRedirectorPath() . '?' . http_build_query($redirectorParams);

		return $this->getImageBannerContent($redirectorUrl);
	}

	/**
	 * @param BannerMachineController $controller
	 * @return string
	 */
	public function getEditModeContent(BannerMachineController $controller)
	{
		return $this->getImageBannerContent('#');
	}

	/**
	 * @param string $redirectorUrl
	 * @return string
	 */
	protected function getImageBannerContent($redirectorUrl)
	{
		$tp = ObjectRepository::getTemplateParser($this);
		$templateLoader = new \Twig_Loader_Filesystem(SUPRA_TEMPLATE_PATH);

		$fileStorage = ObjectRepository::getFileStorage($this);

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerType = $bannerProvider->getType($this->getTypeId());

		$data = array(
				'redirectorUrl' => $redirectorUrl,
				'width' => $bannerType->getWidth(),
				'height' => $bannerType->getHeight(),
				'src' => $fileStorage->getWebPath($this->file)
		);

		$imageBannerContent = $tp->parseTemplate('banner\banner-image.html.twig', $data, $templateLoader);

		return $imageBannerContent;
	}

}
