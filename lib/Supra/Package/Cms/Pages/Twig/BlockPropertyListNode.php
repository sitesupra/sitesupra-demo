<?php

namespace Supra\Package\Cms\Pages\Twig;


class BlockPropertyListNode extends BlockPropertySetNode
{
    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'property list';
    }
}