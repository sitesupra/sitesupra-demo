<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Layout\Exception;

class NoThemeProvider extends ThemeProviderAbstraction
{

	/**
	 * @var NoTheme
	 */
	protected $noTheme;

	/**
	 * @return NoTheme
	 */
	public function getCurrentTheme()
	{
		if (empty($this->noTheme)) {
			$this->noTheme = new NoTheme();
		}

		return $this->noTheme;
	}

}
