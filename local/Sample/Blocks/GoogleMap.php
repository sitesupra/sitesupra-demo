<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class GoogleMap extends BlockConfig
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Google Map')
				->icon('sample:blocks/google-map.png')
				->template('sample:blocks/google-map.html.twig');
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->autoDiscover()	;
	}
}