<?php

namespace Supra\BannerMachine\BannerType;

use Supra\BannerMachine\Entity\ImageBanner;

class AnythingGoesType extends BannerTypeAbstraction
{

	protected function validateImageBanner(ImageBanner $banner)
	{
		return true;
	}

}

