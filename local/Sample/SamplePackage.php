<?php

namespace Sample;

use Sample\Fixtures\Command\DumpFixturesCommand;
use Sample\Fixtures\Command\LoadFixturesCommand;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\AbstractSupraCmsPackage;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultTheme;

class SamplePackage extends AbstractSupraCmsPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		$container->getRouter()->loadConfiguration(
				$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);

		$container->getConsole()->add(new DumpFixturesCommand());
		$container->getConsole()->add(new LoadFixturesCommand());
	}

	public function finish(ContainerInterface $container)
	{
		parent::finish($container);

		$theme = new DefaultTheme();

		$config = $container->getApplication()->getConfigurationSection('sample');

		foreach ($config['layouts'] as $name => $layout) {
			$theme->addLayout($name, $layout['title'], $layout['fileName']);
		}

		$container['cms.pages.theme.provider']->registerTheme($theme);
	}

	public function getBlocks()
	{
		return array(
			new Blocks\Text(),
			new Blocks\Gallery1(),
			new Blocks\Gallery2(),
			new Blocks\GoogleMap(),
			new Blocks\ContactForm(),
			new Blocks\Menu(),
			new Blocks\SocialLinks(),
			new Blocks\Tabs(),
			new Blocks\Accordion(),
			new Blocks\PageMenu(),
			new Blocks\Services(),
			new Blocks\Test(),
		);
	}
}
