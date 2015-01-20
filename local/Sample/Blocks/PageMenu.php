<?php

namespace Sample\Blocks;


use Supra\Package\Cms\Pages\Block\Config\BlockConfig;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;

class PageMenu extends BlockConfig
{
    protected function configureAttributes(AttributeMapper $mapper)
    {
        $mapper->title('Page Menu')
            ->description('Menu based on page structure in Site Map.')
            ->icon('sample:blocks/menu.png')
            ->controller('Sample\Blocks\PageMenuController')
        ;
    }
}