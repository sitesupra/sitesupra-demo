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
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;

class InlineMediaFilter implements FilterInterface, BlockPropertyAware, ContainerAware
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
     * @return string
     */
    public function filter($content, array $options = array())
    {
        $metadata = $this->property->getMetadata();

        $mediaElement = $metadata->offsetExists('media')
            ? $metadata->get('media')->getReferencedElement()
            : null;

        if ($mediaElement === null) {
            return null;
        }

        if ($mediaElement instanceof ImageReferencedElement) {
            return $this->handleImageElement($mediaElement);
        } elseif ($mediaElement instanceof MediaReferencedElement) {
            return $this->handleMediaElement($mediaElement);
        } else {
            return null;
        }
    }

    /**
     * @param ImageReferencedElement $element
     * @return null|string
     */
    protected function handleImageElement(ImageReferencedElement $element)
    {
        $imageId = $element->getImageId();

        $fileStorage = $this->container['cms.file_storage'];
        /* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

        $image = $fileStorage->findImage($imageId);

        if ($image === null) {
            return null;
        }

        $imageSize = $image->findImageSize($element->getSizeName());

        if ($imageSize === null) {
            return null;
        }

        $tag = new HtmlTag('img');

        $width = $imageSize->isCropped() ? $imageSize->getCropWidth() : $imageSize->getWidth();
        $tag->setAttribute('width', $width);

        $height = $imageSize->isCropped() ? $imageSize->getCropHeight() : $imageSize->getCropHeight();
        $tag->setAttribute('height', $height);

        $tag->setAttribute('alt', trim($element->getAlternateText()));

        $tag->setAttribute('src', $fileStorage->getWebPath($image, $imageSize));

        return $tag;
    }

    /**
     * @param MediaReferencedElement $element
     * @return null|string
     */
    protected function handleMediaElement(MediaReferencedElement $element)
    {
        $mediaEmbed = $this->container['cms.media_embed'];
        /* @var $mediaEmbed MediaEmbed */

        $mediaObject = $mediaEmbed->parseUrl($element->getUrl());

        if ($mediaObject === null) {
            return null;
        }

        $metadata = $this->property->getMetadata();

        if ($metadata->offsetExists('width')) {
            $width = (int) $metadata->get('width')->getValue();
            $mediaObject->setWidth($width);
        }

        if ($metadata->offsetExists('height')) {
            $height = (int) $metadata->get('height')->getValue();
            $mediaObject->setHeight($height);
        }

        return $mediaObject->getEmbedCode();
    }

    /**
     * @param BlockProperty $blockProperty
     */
    public function setBlockProperty(BlockProperty $blockProperty)
    {
        $this->property = $blockProperty;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

}