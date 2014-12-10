<?php

namespace Sample;

use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\DependencyInjection\ContainerInterface;
use Sample\Theme\SampleTheme;

class SamplePackage extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		//routing
		$container->getRouter()->loadConfiguration(
				$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);
	}

	public function finish(ContainerInterface $container)
	{
		$themeProvider = $container['cms.pages.theme.provider'];
		/* @var $themeProvider \Supra\Package\Cms\Pages\Layout\Theme\ThemeProviderInterface */

		$themeProvider->registerTheme(
				new SampleTheme(array(
					new Theme\Layout\SimpleLayout(),
					new Theme\Layout\TwoColumnLayout()
				)
			));

		// blocks
		$blockCollection = $container['cms.pages.blocks.collection'];
		/* @var $blockCollection \Supra\Package\Cms\Pages\Block\BlockCollection */
		$blockCollection->add(array(
			new Blocks\Text(),
			new Blocks\Gallery(),
			new Blocks\GoogleMap(),
			new Blocks\ContactForm(),
			new Blocks\Menu(),
			new Blocks\SocialLinks(),

//			new Tabs(),
//			new Accordion(),
//			new ImageSlider() ???
//			new Testimonials() ???
		));
	}
}