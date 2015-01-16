<?php

namespace Sample;

use Supra\Package\Cms\AbstractSupraCmsPackage;
use Supra\Core\DependencyInjection\ContainerInterface;

class SamplePackage extends AbstractSupraCmsPackage
{
	public function inject(ContainerInterface $container)
	{
		$container->getRouter()->loadConfiguration(
				$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);
	}

	public function getBlocks()
	{
		return array(
			new Blocks\Text(),
			new Blocks\Gallery(),
			new Blocks\GoogleMap(),
			new Blocks\ContactForm(),
			new Blocks\Menu(),
			new Blocks\SocialLinks(),
			
			new Blocks\CollectionGallery(),
			new Blocks\Test(),
			new Blocks\Tabs(),
			new Blocks\Accordion(),
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
