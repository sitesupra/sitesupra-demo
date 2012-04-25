<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Pages\Entity\Theme;

abstract class ThemeProviderAbstraction
{
	
	/**
	 * @return Theme
	 */
	abstract function getCurrentTheme();
}