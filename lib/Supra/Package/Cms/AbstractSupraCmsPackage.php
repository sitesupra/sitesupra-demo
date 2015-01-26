<?php

namespace Supra\Package\Cms;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultTheme;

abstract class AbstractSupraCmsPackage extends AbstractSupraPackage
{
	public function finish(ContainerInterface $container)
	{
		// blocks
		$container['cms.pages.blocks.collection']->add($this->getBlocks(), $this);
	}

	/**
	 * @return array
	 */
	public function getBlocks()
	{
		return array();
	}
}