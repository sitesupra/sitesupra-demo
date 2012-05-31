<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class ButtonParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $buttons;

	public function makeDesignData(&$designData)
	{
		$designData['buttons'] = $this->buttons;
	}

}
