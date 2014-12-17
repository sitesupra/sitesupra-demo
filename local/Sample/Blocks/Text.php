<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\CacheMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class Text extends BlockConfiguration
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Text Block')
				->description('Text Block with wysiwyg editor.')
				->icon('sample:blocks/text.png')
				->template('sample:blocks/text.html.twig')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->add('content', 'html');
	}

	protected function configureCache(CacheMapper $mapper)
	{
		$this->cache = $mapper;
	}
}