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
	 * @return ThemeLayout 
	 */
	abstract public function getCurrentThemeLayoutForTemplate(Template $template, $media = TemplateLayout::MEDIA_SCREEN);

	/**
	 * @return array
	 */
	abstract public function getAllThemes();

	/**
	 * @return Theme 
	 */
	abstract public function getThemeByName($themeName);

	/**
	 * @param Theme 
	 */
	abstract public function storeTheme(Theme $theme);

	/**
	 * @return Theme
	 */
	abstract public function makeNewTheme();
}