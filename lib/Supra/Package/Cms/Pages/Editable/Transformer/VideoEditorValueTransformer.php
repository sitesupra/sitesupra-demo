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

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use MediaEmbed\MediaEmbed;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Package\Cms\Editable\Exception\TransformationFailedException;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class VideoEditorValueTransformer implements ValueTransformerInterface, ContainerAware, BlockPropertyAware
{
    /**
     * @var BlockProperty
     */
    protected $property;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param BlockProperty $blockProperty
     */
    public function setBlockProperty(BlockProperty $blockProperty)
    {
        $this->property = $blockProperty;
    }

    /**
     * @param mixed $value
     * @return null
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            $this->property->getMetadata()->clear();
            return null;
        }

        if (! is_array($value)) {
            throw new TransformationFailedException(sprintf(
                'Expected array only, got [%s]', gettype($value)
            ));
        }

        if (! isset($value['url'])) {
            throw new TransformationFailedException('Media URL is missing.');
        }

        $mediaEmbed = $this->container['cms.media_embed'];
        /* @var $mediaEmbed MediaEmbed */

        $mediaObject = $mediaEmbed->parseUrl($value['url']);

        if ($mediaObject === null) {
            throw new TransformationFailedException(sprintf(
                'Failed to parse media URL [%s].',
                $value['url']
            ));
        }

        $metadata = $this->property->getMetadata();

        $mediaElement = new MediaReferencedElement();

        $mediaElement->setUrl($value['url']);

        if (isset($value['width'])) {

            $width = (int) $value['width'];

            if ($width < 1 ) {
                throw new TransformationFailedException(sprintf(
                    'Invalid width value: [%s]', $value['width']
                ));
            }

            $mediaElement->setWidth($width);
        }

        if (isset($value['height'])) {

            $height = (int) $value['height'];

            if ($height < 1 ) {
                throw new TransformationFailedException(sprintf(
                    'Invalid height value: [%s]', $value['height']
                ));
            }

            $mediaElement->setHeight($height);
        }

        if (! isset($metadata['media'])) {
            $this->property->addMetadata(new BlockPropertyMetadata('media', $this->property));
        }

        $metadata['media']->setReferencedElement($mediaElement);

        return $value['url'];
    }

    /**
     * @param mixed $value
     * @return null|array
     */
    public function transform($value)
    {
        $metadata = $this->property->getMetadata();

        if (! isset($metadata['media'])) {
            return null;
        }

        $element = $metadata['media']->getReferencedElement();

        return $element ? $element->toArray() : null;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

}
