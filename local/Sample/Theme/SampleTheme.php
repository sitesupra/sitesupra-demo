<?php

namespace Sample\Theme;

use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;

class SampleTheme implements ThemeInterface
{
	public function getName()
	{
		return 'sample';
	}

	public function getLayouts()
	{
		return array(
			'simple' => new Layout\SimpleLayout(),
			'two_column' => new Layout\TwoColumnLayout(),
		);
	}
}