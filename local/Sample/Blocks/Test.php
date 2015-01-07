<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;

class Test extends BlockConfig
{
    /**
     * {@inheritDoc}
     */
    protected function configureAttributes(AttributeMapper $mapper)
    {
        $mapper->title('Test');
    }
}