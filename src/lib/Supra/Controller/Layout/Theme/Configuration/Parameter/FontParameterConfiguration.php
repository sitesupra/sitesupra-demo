<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class FontParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $fonts;

	/**
	 * @var string
	 */
	public $listName;

	/**
	 * @param array $designData
	 */
	public function makeDesignData(&$designData)
	{
		$designData['fonts'][$this->listName] = $this->fonts;
	}

	/**
	 * @param array $outputValue
	 */
	public function makeOutputValue(&$outputValue)
	{
		foreach ($this->fonts as $fontData) {

			if ($fontData['title'] == $outputValue) {
				$outputValue = $fontData;
				break;
			}
		}
	}

}