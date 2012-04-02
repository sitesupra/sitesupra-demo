<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Layout\Exception;

class NoThemeProvider extends ThemeProviderAbstraction
{
	protected $noTheme;
	
	function __construct()
	{
		$this->noTheme = new NoTheme();
	}

	public function getActiveTheme()
	{
		return $this->noTheme;
	}

	public function setActiveTheme(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	public function storeThemeParameters(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

}
