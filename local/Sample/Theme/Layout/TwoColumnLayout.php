<?php

namespace Sample\Theme\Layout;

use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

class TwoColumnLayout implements ThemeLayoutInterface
{
	public function getName()
	{
		return 'two_columns';
	}

	public function getTitle()
	{
		return 'Title';
	}

	public function getIcon()
	{
		return null;
	}

	public function getFileName()
	{
		return 'Sample:layouts/two_columns.html.twig';
	}
}