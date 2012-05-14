<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Controller\Pages\Entity\Theme;

class ThemeConfigurationLoader extends ComponentConfigurationLoader
{

	const MODE_FETCH_CONFIGURATION = 'fetch';
	const MODE_READ_CONFIGURATION = 'read';

	/**
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @var string
	 */
	protected $mode = self::MODE_READ_CONFIGURATION;

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param string $mode 
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}

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
