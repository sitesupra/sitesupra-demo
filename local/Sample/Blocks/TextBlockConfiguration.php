<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\BlockMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class TextBlockConfiguration extends BlockConfiguration
{
	protected function configureBlock(BlockMapper $mapper)
	{
		$mapper->title('Text Block')
				->description('Text Block with wysiwyg editor.');
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->add('content', 'html');
	}
}