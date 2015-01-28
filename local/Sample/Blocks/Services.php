<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;

class Services extends BlockConfig
{
    /**
     * {@inheritDoc}
     */
    protected function configureAttributes(AttributeMapper $mapper)
    {
        $mapper->title('Services')
            ->icon('sample:blocks/gallery.png');
    }
}
