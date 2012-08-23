<?php

namespace Supra\Controller\Pages\Entity\Theme\Parameter;

use Supra\Controller\Pages\Entity\Theme\ThemeParameterValue;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\ImageSize;

/**
 * @Entity
 */
class ImageParameter extends ThemeParameterAbstraction
{

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @param mixed $input
	 */
	public function updateParameterValue(ThemeParameterValue $parameterValue, $input)
	{
		$filestorage = ObjectRepository::getFileStorage($this);

		if (isset($input['image'])) {

			$image = $filestorage->find($input['image']);

			if ( ! empty($image)) {

				$variantName = $filestorage->createImageVariant(
						$image, $input['size_width'], $input['size_height'], $input['crop_left'], $input['crop_top'], $input['crop_width'], $input['crop_height']
				);

				$input['variant_name'] = $variantName;
			}
		}

		$parameterValue->setValue(serialize($input));
	}

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$outputValue = unserialize($parameterValue->getValue());

		$filestorage = ObjectRepository::getFileStorage($this);

		$image = $filestorage->find($outputValue['image']);

		if ( ! empty($image)) {
			/* @var $image Image */
			$outputValue['image'] = $filestorage->getFileInfo($image);
			$outputValue['url'] = $filestorage->getWebPath($image, $outputValue['variant_name']);
		} else {
			$outputValue = null;
		}

		return $outputValue;
	}

}
