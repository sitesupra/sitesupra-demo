<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\MenuParameter;

class MenuParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	/**
	 * @var array
	 */
	public $menus;

	public function makeDesignData(&$designData)
	{
		$designData['menus'] = $this->menus;
	}

	protected function getParameterClass()
	{
		return MenuParameter::CN();
	}

}
