<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Layout\Exception;

class NoThemeProvider extends ThemeProviderAbstraction
{

	/**
	 * @var NoTheme
	 */
	protected $noTheme;

	function __construct()
	{
		$this->noTheme = new NoTheme();
	}

	/**
	 * @return NoTheme
	 */
	public function getActiveTheme()
	{
		return $this->noTheme;
	}

	/**
	 * @param ThemeInterface $theme
	 * @throws Exception\RuntimeException 
	 */
	public function setActiveTheme(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	/**
	 * @param ThemeInterface $theme
	 * @throws Exception\RuntimeException 
	 */
	public function storeThemeParameters(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	/**
	 * @return NoTheme
	 */
	public function getCurrentTheme()
	{
		return $this->noTheme;
	}

	public function setCurrentTheme(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

}
