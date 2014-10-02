<?php

namespace Sample;

use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeProviderInterface;

class SamplePackage extends AbstractSupraPackage
{
	public function finish(ContainerInterface $container)
	{
		// @TODO: ::getThemeProvider()?
		$themeProvider = $container['cms.theme_provider'];
		/** @var $themeProvider ThemeProviderInterface */

		$themeProvider->registerTheme(new Theme\SampleTheme());
	}
}