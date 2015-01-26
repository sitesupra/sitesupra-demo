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

use MediaEmbed\MediaEmbed;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class VideoFilter implements FilterInterface, ContainerAware, BlockPropertyAware
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var BlockProperty
     */
    protected $property;

    /**
     * {@inheritDoc}
     */
    public function filter($content, array $options = array())
    {
        $metadata = $this->property->getMetadata();

        if (! isset($metadata['media'])) {
            return null;
        }

        $element = $metadata['media']->getReferencedElement();

        if (! $element instanceof MediaReferencedElement) {
            return null;
        }

        $mediaEmbed = $this->container['cms.media_embed'];
        /* @var $mediaEmbed MediaEmbed */

        $mediaObject = $mediaEmbed->parseUrl($element->getUrl());

        if ($mediaObject === null) {
            return null;
        }

        $mediaObject->setWidth($element->getWidth());
        $mediaObject->setHeight($element->getHeight());

        return $mediaObject->getEmbedCode();
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param BlockProperty $blockProperty
     */
    public function setBlockProperty(BlockProperty $blockProperty)
    {
        $this->property = $blockProperty;
    }
}
