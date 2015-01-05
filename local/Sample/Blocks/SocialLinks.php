<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

class SocialLinks extends BlockConfig
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Social links')
				->icon('sample:blocks/social-links.png')
				->template('sample:blocks/social-links.html.twig')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$mapper->autoDiscover();
	}
}