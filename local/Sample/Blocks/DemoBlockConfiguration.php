<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\BlockMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class DemoBlockConfiguration extends BlockConfiguration
{
	protected function configureBlock(BlockMapper $mapper)
	{
		$mapper->title('Demo Block')
				->description('Collection of different properties.')
				->icon('sample:blocks/demo.png')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->add('string', 'string', 'String')
				->add('inlineString', 'inline_string')
				->add('text', 'text', 'Text')
				->add('inlineText', 'inline_text')
				->add('checkbox', 'checkbox', 'Checkbox')
				;
	}
}