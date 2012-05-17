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

	public function makeDesignData(&$designData)
	{
		$designData['fonts'][$this->listName] = $this->fonts;
	}

}