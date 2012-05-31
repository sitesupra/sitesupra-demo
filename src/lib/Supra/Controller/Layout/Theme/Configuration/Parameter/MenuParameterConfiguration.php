<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

class MenuParameterConfiguration extends ThemeParameterConfiguration
{

	/**
	 * @var array
	 */
	public $menus;

	public function makeDesignData(&$designData)
	{
		$designData['menus'] = $this->menus;
	}

}
