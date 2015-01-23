<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;

class Accordion extends BlockConfig
{
    /**
     * {@inheritDoc}
     */
    protected function configureAttributes(AttributeMapper $mapper)
    {
        $mapper->title('Accordion')
            ->icon('sample:blocks/accordion.png')
            ->description('Allows you to format text in accordion');
    }
}
