<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class GoogleMapBlock extends BlockConfiguration
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Google Map')
				->icon('sample:icons/google-map.png')
				->template('sample:blocks/google-map.html.twig');
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->autoDiscover()	;
	}
}