<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class DemoBlock extends BlockConfiguration
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Demo Block')
				->description('Collection of different properties.')
				->icon('sample:blocks/demo.png')
				->template('sample:blocks/demo.html.twig')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->autoDiscover()
				->add('foo_1', 'string', array('label' => 'Foo 1'))
				->add('foo_5', 'string', array('label' => 'Foo 5'))
				;
	}
}