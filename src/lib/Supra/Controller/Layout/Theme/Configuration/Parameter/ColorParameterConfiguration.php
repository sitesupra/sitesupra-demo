<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class ColorParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $colors = array();

	public function makeDesignData(&$designData)
	{
		if ($this->visible && ! empty($this->colors)) {
			$designData['colors'] = $this->colors;
		}
	}

}
