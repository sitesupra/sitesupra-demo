<?php

namespace Supra\BannerMachine;

use Supra\FileStorage\FileEventArgs;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\File;
use Supra\BannerMachine\Entity\FileBanner;

class EventListener
{

	public function preFileDelete(FileEventArgs $eventArgs)
	{
		$file = $eventArgs->getFile();

		if ($file instanceof File) {

			$bannerProvider = ObjectRepository::getBannerProvider($this);

			$banners = $bannerProvider->getBannersByFile($file);

			if ( ! empty($banners)) {
				
				$lm = ObjectRepository::getLocaleManager($this);
				
				$locales = array();
				
				foreach($banners as $banner) {
					/* @var $banner FileBanner */
					$locale = $lm->getLocale($banner->getLocaleId());
					
					if(!empty($locale)) {
						$locales[] = $locale->getTitle();
					}
					else {
						$locales[] = '[' . $banner->getLocaleId() . ']';
					}
				}
				
				throw new CmsException(null, 'This file can\'t be removed because it is used as a banner in locales: ' . join(', ', $locales));
			}
		}
	}

}
