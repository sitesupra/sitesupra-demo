<?php

namespace Supra\Cms\BannerManager\Bannerlist;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\SizeType;
use Supra\BannerMachine\Entity\Banner;
use Supra\BannerMachine\Entity\ImageBanner;

class BannerlistAction extends CmsAction
{

	public function loadAction()
	{
		$bannerList = array();

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$fileStorage = ObjectRepository::getFileStorage($this);

		$bannerSizeTypes = $bannerProvider->getSizeTypes();

		foreach ($bannerSizeTypes as $bannerSizeType) {
			/* @var $bannerSizeType SizeType */

			$banners = $bannerProvider->getBanners($bannerSizeType);

			$children = array();

			foreach ($banners as $banner) {
				/* @var $banner Banner */

				$externalPath = '#';
				
				if ($banner instanceof ImageBanner) {
					$externalPath = $fileStorage->getWebPath($banner->getFile());
				}

				$children[] = array(
						'banner_id' => $banner->getId(),
						'external_path' => $externalPath,
						'width' => $bannerSizeType->getWidth(),
						'height' => $bannerSizeType->getHeight(),
						'status' => $banner->getStatus(),
						'schedule' => array(
								'from' => $banner->getScheduledFrom(),
								'to' => $banner->getScheduledTill()),
						'title' => $banner->getTitle()
				);
			}

			$bannerList[] = array(
					'group_id' => $bannerSizeType->getId(),
					'title' => $bannerSizeType->getName(),
					'children' => $children
			);
		}

		$this->getResponse()->setResponseData($bannerList);
	}

}
