<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class BackgroundParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $backgrounds;
	
	/**
	 * @var string
	 */
	public $listName;

	/**
	 * @param array $designData
	 */
	public function makeDesignData(&$designData)
	{	
		$designData['backgrounds'][$this->listName] = $this->backgrounds;
	}

	/**
	 * @param string $outputValue
	 */
	public function makeOutputValue(&$outputValue)
	{
		foreach ($this->backgrounds as $backgroundData) {

			if ($backgroundData['id'] == $outputValue) {
				
				$backgroundData['icon'] = "'" . $backgroundData['icon'] . "'";
				$backgroundData['icon'] = str_replace('//', '/', $backgroundData['icon']);
				
				$outputValue = $backgroundData;
				break;
			}
		}
	}

}
