<?php

namespace Supra\BannerMachine\Entity;

use Supra\BannerMachine\BannerMachineController;
use Supra\Html\HtmlTag;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\File;
use Supra\Cms\Exception\CmsException;
use Supra\BannerMachine\BannerMachineRedirector;

/**
 * @Entity
 */
class FlashBanner extends FileBanner
{
	const MIME_TYPE = 'application/x-shockwave-flash';

	/**
	 * @param BannerMachineController $controller 
	 * @return string
	 */
	public function getExposureModeContent(BannerMachineController $controller)
	{
		$redirectorUrl = null;

		if ($this->hasTarget()) {

			$bannerProvider = ObjectRepository::getBannerProvider($this);

			$redirectorParams = array(
				BannerMachineRedirector::REQUEST_KEY_BANNER_ID => $this->getId(),
				BannerMachineRedirector::REQUEST_KEY_EXTRA => $controller->getPropertyValue(BannerMachineController::PROPERTY_NAME_APPEND_TO_URL),
				BannerMachineRedirector::REQUEST_KEY_PAGE_REF => $controller->getPage()->getId()
			);

			$redirectorUrl = $bannerProvider->getRedirectorPath() . '?' . http_build_query($redirectorParams);
		}

		return $this->getFlashBannerContent($redirectorUrl);
	}

	/**
	 * @param BannerMachineController $controller
	 * @return string
	 */
	public function getEditModeContent(BannerMachineController $controller)
	{
		return $this->getFlashBannerContent('/trololo123');
	}

	/**
	 * @param File $file 
	 */
	public function setFile(File $file)
	{
		if ($file->getMimeType() != self::MIME_TYPE) {
			throw new CmsException(null, 'Can not change type of banner file!');
		}

		parent::setFile($file);
	}

	/**
	 * @return string
	 */
	public function getExternalPath()
	{
		return '/cms/lib/supra/build/medialibrary/assets/skins/supra/images/icons/file-swf-large.png';
	}

	/**
	 * @param string $redirectorUrl
	 * @return string
	 */
	protected function getFlashBannerContent($redirectorUrl)
	{
		$tp = ObjectRepository::getTemplateParser($this);
		$templateLoader = new \Twig_Loader_Filesystem(SUPRA_TEMPLATE_PATH);

		$fileStorage = ObjectRepository::getFileStorage($this);

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerType = $bannerProvider->getType($this->getTypeId());

		$data = array(
			'width' => $bannerType->getWidth(),
			'height' => $bannerType->getHeight(),
			'swfUrl' => $fileStorage->getWebPath($this->file)
		);

		if ( ! empty($redirectorUrl)) {
			$data['clickTag'] = urldecode($redirectorUrl);
		}

		$imageBannerContent = $tp->parseTemplate('banner\banner-flash.html.twig', $data, $templateLoader);

		return $imageBannerContent;
	}

}
