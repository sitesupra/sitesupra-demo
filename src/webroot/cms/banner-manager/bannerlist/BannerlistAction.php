<?php

namespace Supra\Cms\BannerManager\Bannerlist;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\Entity\Banner;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;
use Supra\BannerMachine\Entity\ImageBanner;

class BannerlistAction extends CmsAction
{

	public function loadAction()
	{
		$bannerList = array();

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$fileStorage = ObjectRepository::getFileStorage($this);

		$bannerTypes = $bannerProvider->getTypes();

		foreach ($bannerTypes as $bannerType) {
			/* @var $bannerType BannerTypeAbstraction */

			$banners = $bannerProvider->getBanners($bannerType);

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
						'width' => $bannerType->getWidth(),
						'height' => $bannerType->getHeight(),
						'status' => $banner->getStatus(),
						'schedule' => array(
								'from' => $banner->getScheduledFrom(),
								'to' => $banner->getScheduledTill()),
						'title' => $banner->getTitle()
				);
			}

			$bannerList[] = array(
					'group_id' => $bannerType->getId(),
					'title' => $bannerType->getName(),
					'children' => $children
			);
		}

		$this->getResponse()->setResponseData($bannerList);
	}

}
