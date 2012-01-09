<?php

namespace Supra\Cms\BannerManager\Bannerlist;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\Entity\Banner;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;
use Supra\BannerMachine\Entity\ImageBanner;
use Supra\BannerMachine\Entity\FlashBanner;

class BannerlistAction extends CmsAction
{

	public function loadAction()
	{
		$request = $this->getRequest();
		
		$bannerList = array();

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerTypes = $bannerProvider->getTypes();
		
		$localeId = $request->getParameter('locale');
		
		foreach ($bannerTypes as $bannerType) {
			/* @var $bannerType BannerTypeAbstraction */

			$banners = $bannerProvider->getBanners($bannerType, $localeId);

			$children = array();

			foreach ($banners as $banner) {
				/* @var $banner Banner */

				$children[] = array(
						'banner_id' => $banner->getId(),
						'external_path' => $banner->getExternalPath(),
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
