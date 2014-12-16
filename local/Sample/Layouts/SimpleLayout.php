<?php

namespace Sample\Layouts;

use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

class SimpleLayout implements ThemeLayoutInterface
{
	public function getName()
	{
		return 'simple';
	}

	public function getTitle()
	{
		return 'Simple';
	}

	public function getIcon()
	{
		return null;
	}

	public function getFileName()
	{
		return 'SamplePackage:layouts/simple.html.twig';
	}
}
