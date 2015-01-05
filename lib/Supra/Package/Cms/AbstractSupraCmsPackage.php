<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultTheme;

abstract class AbstractSupraCmsPackage extends AbstractSupraPackage
{
	public function finish(ContainerInterface $container)
	{
		$theme = $this->getTheme();

		foreach ($this->getLayouts() as $layout) {
			$theme->addLayout($layout);
		}

		$container['cms.pages.theme.provider']->registerTheme($theme);

		// blocks
		$container['cms.pages.blocks.collection']->add($this->getBlocks(), $this);
	}

	/**
	 * @return array
	 */
	public function getLayouts()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function getBlocks()
	{
		return array();
	}

	/**
	 * @return \Supra\Package\Cms\Pages\Layout\Theme\DefaultTheme
	 */
	public function getTheme()
	{
		return new DefaultTheme();
	}
}