<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\BlockMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class GalleryBlockConfiguration extends BlockConfiguration
{
	protected function configureBlock(BlockMapper $mapper)
	{
		$mapper->title('Image Gallery')
				->description('Images collection.')
				->icon('sample:blocks/gallery.png')
				->cmsClassName('Gallery')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->add('imageGallery', 'gallery', 'Image Gallery')
				->add('title', 'string', 'Block Title')
				;
	}
}