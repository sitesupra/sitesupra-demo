<?php

namespace Sample;

use Supra\Package\Cms\AbstractSupraCmsPackage;

class SamplePackage extends AbstractSupraCmsPackage
{
	public function getBlocks()
	{
		return array(
			new Blocks\Text(),
			new Blocks\Gallery(),
			new Blocks\GoogleMap(),
			new Blocks\ContactForm(),
			new Blocks\Menu(),
			new Blocks\SocialLinks(),
		);
	}

	public function getLayouts()
	{
		return array(
			new Layouts\SimpleLayout(),
			new Layouts\TwoColumnLayout(),
		);
	}
}