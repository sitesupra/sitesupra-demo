<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;

class Tabs extends BlockConfig
{
    /**
     * {@inheritDoc}
     */
    protected function configureAttributes(AttributeMapper $mapper)
    {
        $mapper->title('Tabs')
            ->icon('sample:blocks/tabs.png')
            ->description('Allows you to format text in tabs');
    }
}
