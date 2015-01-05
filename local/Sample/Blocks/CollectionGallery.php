<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class CollectionGallery extends BlockConfig
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Collection Gallery (TEST)')
				->icon('sample:blocks/gallery.png')
				->cmsClassName('Gallery')
				->template('sample:blocks/collection-gallery.html.twig')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->addList('images', $mapper->createProperty('image', 'image'));

		$mapper->addList('tabs',
				$mapper->createPropertySet('tab', array(
					$mapper->createProperty('title', 'string'),
					$mapper->createProperty('content', 'html')
		)));

		$mapper->addSet('foo', array(
			$mapper->createProperty('title', 'string'),
			$mapper->createProperty('content', 'html'),
		));
	}
}