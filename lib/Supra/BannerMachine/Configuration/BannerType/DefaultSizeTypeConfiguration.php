<?php

namespace Supra\BannerMachine\Configuration\BannerType;

use Supra\BannerMachine\BannerType\DefaultSizeType;

class DefaultSizeTypeConfiguration extends BannerTypeConfigurationAbstraction
{
	/**
	 * @var float
	 */
	public $ratioDelta;
	
	/**
	 * @return DefaultSizeType
	 */
	protected function makeType()
	{
		return new DefaultSizeType();
	}
	
	/**
	 * Additionally configure the ratio delta
	 */
	public function configure() {
		
		parent::configure();
		
		$this->type->setRatioDelta($this->ratioDelta);
	}

}
