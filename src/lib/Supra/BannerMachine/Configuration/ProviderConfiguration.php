<?php

namespace Supra\BannerMachine\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerProvider;
use Supra\BannerMachine\Exception;
use Supra\BannerMachine\SizeType;

class ProviderConfiguration implements ConfigurationInterface
{

	public $id;
	public $sizeTypes;
	public $path;
	public $namespace;

	public function configure()
	{
		$provider = new BannerProvider();

		$sizeTypes = array();

		foreach ($this->sizeTypes as $sizeTypeConfiguration) {
			/* @var $sizeTypeConfiguration SizeTypeConfiguration */

			$sizeType = $sizeTypeConfiguration->getSizeType();

			$sizeTypes[$sizeType->getId()] = $sizeType;
		}

		$provider->setSizeTypes($sizeTypes);

		ObjectRepository::setDefaultBannerProvider($provider);
		ObjectRepository::setBannerProvider('#' . $this->id, $provider);
	}

}

