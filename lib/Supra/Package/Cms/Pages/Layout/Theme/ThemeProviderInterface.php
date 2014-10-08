<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

interface ThemeProviderInterface
{
	public function getActiveTheme();
	public function registerTheme(ThemeInterface $theme);
}