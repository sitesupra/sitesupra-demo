<?php

namespace Supra\BannerMachine\Configuration\BannerType;

use Supra\BannerMachine\BannerType\AnythingGoesType;

class AnythingGoesTypeConfiguration extends BannerTypeConfigurationAbstraction
{

	public function configure()
	{
		$this->width = 200;
		$this->height = 200;

		parent::configure();
	}

	protected function makeType()
	{
		$this->type = new AnythingGoesType();
	}
	
}

