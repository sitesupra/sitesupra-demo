<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Pages\Entity\Theme;
use Supra\Controller\Pages\Entity\Template;

abstract class ThemeProviderAbstraction
{

	/**
	 * @return Theme
	 */
	abstract public function getCurrentTheme();
	
	/**
	 * @return Theme
	 */
	abstract public function getActiveTheme();	

	/**
	 * @return ThemeLayout 
	 */
	abstract public function getCurrentThemeLayoutForTemplate(Template $template, $media = TemplateLayout::MEDIA_SCREEN);
}