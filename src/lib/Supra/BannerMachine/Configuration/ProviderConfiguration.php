<?php

namespace Supra\BannerMachine\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerProvider;
use Supra\BannerMachine\Exception;
use Supra\BannerMachine\Configuration\BannerType\BannerTypeConfigurationAbstraction;


class ProviderConfiguration implements ConfigurationInterface
{

	public $id;
	public $types;
	public $path;
	public $namespace;

	public function configure()
	{
		$provider = new BannerProvider();

		$types = array();

		foreach ($this->types as $typeConfiguration) {
			/* @var $typeConfiguration BannerTypeConfigurationAbstraction */

			$type = $typeConfiguration->getType();

			$types[$type->getId()] = $type;
		}

		$provider->setTypes($types);

		ObjectRepository::setDefaultBannerProvider($provider);
		ObjectRepository::setBannerProvider('#' . $this->id, $provider);
	}

}

