<?php

namespace Sample\Layouts;

use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

class TwoColumnLayout implements ThemeLayoutInterface
{
	public function getName()
	{
		return 'two_columns';
	}

	public function getTitle()
	{
		return 'Two Columns';
	}

	public function getIcon()
	{
		return null;
	}

	public function getFileName()
	{
		return 'SamplePackage:layouts/two_columns.html.twig';
	}
}