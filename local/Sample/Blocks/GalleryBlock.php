<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class GalleryBlock extends BlockConfiguration
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Image Gallery')
				->description('Images collection.')
				->icon('sample:blocks/gallery.png')
				->cmsClassName('Gallery')
				->template('sample:blocks/gallery.html.twig')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->autoDiscover();
	}
}