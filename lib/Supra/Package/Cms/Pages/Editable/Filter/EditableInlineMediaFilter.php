<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class EditableInlineMediaFilter implements FilterInterface, BlockPropertyAware
{
    /**
     * @var BlockProperty
     */
    protected $blockProperty;

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function filter($content, array $options = array())
    {
        return sprintf(
            '<div id="content_%s_%s" class="su-content-inline su-input-media-inline-content">%s</div>',
            $this->blockProperty->getBlock()->getId(),
            str_replace('.', '_', $this->blockProperty->getHierarchicalName()),
            $content
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockProperty(BlockProperty $blockProperty)
    {
        $this->blockProperty = $blockProperty;
    }
}
