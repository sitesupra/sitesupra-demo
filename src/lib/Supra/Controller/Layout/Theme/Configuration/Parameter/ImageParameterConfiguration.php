<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Theme\Parameter\ImageParameter;

class ImageParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	public function makeOutputValue(&$outputValue)
	{
		$filestorage = ObjectRepository::getFileStorage($this);
		$outputValue = unserialize($outputValue);
		if ( ! empty($outputValue)) {

			/* @var $image \Supra\FileStorage\Entity\Image */
			$image = $filestorage->find($outputValue['image']);

			$outputValue = $image->getInfo();
		}
	}

	public function makeStoreValue(&$storeValue)
	{
		$storeValue = serialize($storeValue);
	}

	protected function getParameterClass()
	{
		return ImageParameter::CN();
	}

}
