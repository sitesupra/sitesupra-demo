<?php

namespace Supra\BannerMachine\Configuration\BannerType;

use Supra\BannerMachine\BannerType\AnythingGoesType;

class CustomTypeConfiguration extends BannerTypeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $typeClassname = 'Supra\BannerMachine\BannerType\DefaultSizeType';
	
	/**
	 * @return BannerTypeAbstraction
	 */
	protected function makeType()
	{
		$type = Loader::getClassInstance($this->typeClassname, BannerTypeAbstraction::CN);
		/* @var $type BannerTypeAbstraction */
		
		return $type;
	}
	
}
