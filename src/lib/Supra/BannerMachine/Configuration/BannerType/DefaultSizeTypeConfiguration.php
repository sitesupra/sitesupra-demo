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
	 * @var DefaultSizeType
	 */
	protected $type;

	protected function makeType()
	{
		$this->type = new DefaultSizeType();
	}
	
	public function configure() {
		
		parent::configure();
		
		$this->type->setRatioDelta($this->ratioDelta);
	}

}
