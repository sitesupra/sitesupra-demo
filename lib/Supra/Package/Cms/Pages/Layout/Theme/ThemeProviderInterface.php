<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

interface ThemeProviderInterface
{
	/**
	 * @return ThemeInterface
	 */
	public function getActiveTheme();

	/**
	 * @param ThemeInterface $theme
	 * @return void
	 */
	public function registerTheme(ThemeInterface $theme);
}