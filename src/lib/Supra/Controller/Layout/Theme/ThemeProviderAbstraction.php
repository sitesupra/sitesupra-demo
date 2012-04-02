<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Layout\Exception;

abstract class ThemeProviderAbstraction
{

	/**
	 * @var array
	 */
	protected $themes;

	/**
	 * @return array
	 */
	public function getThemes()
	{
		return $this->themes;
	}

	/**
	 * @param ThemeInterface $theme 
	 */
	public function addTheme(ThemeInterface $theme)
	{
		$this->themes[$theme->getName()] = $theme;
	}

	/**
	 * @return array
	 */
	public function getEnabledThemes()
	{
		$allThemes = $this->getThemes();

		$enabledThemes = array();

		foreach ($allThemes as $themeName => $theme) {
			/* @var $theme SimopleTheme */

			if ($theme->getEnabled()) {
				$enabledThemes[$themeName] = $theme;
			}
		}

		return $enabledThemes;
	}

	/**
	 * @param string $themeName
	 * @return ThemeInterface
	 * @throws Exception\RuntimeException 
	 */
	public function getTheme($themeName)
	{
		if ( ! isset($this->themes[$themeName])) {
			throw new Exception\RuntimeException('There is no theme "' . $themeName . '" configured.');
		}

		return $this->themes[$themeName];
	}

	abstract public function storeThemeParameters(ThemeInterface $theme);

	/**
	 * @return ThemeInterface
	 */
	abstract public function getActiveTheme();

	/**
	 * @param ThemeInterface 
	 */
	abstract public function setActiveTheme(ThemeInterface $theme);
}

