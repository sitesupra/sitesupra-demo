<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Controller\Pages\Entity\Theme;

class ThemeConfigurationLoader extends ComponentConfigurationLoader
{

	/**
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme)
	{
		$this->theme = $theme;
	}

}
