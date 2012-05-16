<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class BackgroundParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $backgrounds;

	public function makeDesignData(&$designData)
	{
		$designData['backgrounds'] = $this->backgrounds;
	}

}
