<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Layout\Theme\Theme;

class ThemeConfiguration implements ConfigurationInterface
{
	/*
	 *         name: nyancat
	  title: Nyancat Theme
	  description: NYANCAT FOR GREAT JUSTICE!
	  parameters:
	 * 
	  - Supra\Controller\Layout\Theme\ThemeParameterConfiguration:
	  name: headerBackgroundUrl
	  value: /resources/themes/nyancat/images/nyancat.gif
	 * 
	  - Supra\Controller\Layout\Theme\EditableThemeParameterConfiguration:
	  name: footerBackgroundUrl
	  defaultValue: /resources/themes/nyancat/images/nyancat.gif
	  type: url
	  description: Soem footar bak graun UREL!

	 */

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var boolean
	 */
	public $enabled;

	/**
	 * @var string
	 */
	public $parameters;

	/**
	 * @var Theme
	 */
	protected $theme;

	public function configure()
	{
		$theme = new Theme();

		$theme->setName($this->name);
		$theme->setDescription($this->description);
		$theme->setEnabled($this->enabled);

		$parameterConfigurations = array();

		if ( ! empty($this->parameters)) {
			
			foreach ($this->parameters as $parameterConfiguration) {
				/* @var $parameterConfiguration ThemeParameterConfiguration */

				$name = $parameterConfiguration->name;
				$parameterConfigurations[$name] = $parameterConfiguration;
			}
		}

		$theme->setParameterConfigurations($parameterConfigurations);

		$this->theme = $theme;
	}

	public function getTheme()
	{
		return $this->theme;
	}

}
