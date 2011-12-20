<?php

namespace Supra\BannerMachine;

use Supra\FileStorage\FileEventArgs;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\File;

class EventListener
{

	public function preFileDelete(FileEventArgs $eventArgs)
	{
		$file = $eventArgs->getFile();

		if ($file instanceof File) {

			$bannerProvider = ObjectRepository::getBannerProvider($this);

			$banners = $bannerProvider->getBannersByFile($file);

			if ( ! empty($banners)) {
				throw new CmsException(null, 'Can not delete this file because it is used in banner manager.');
			}
		}
	}

}
