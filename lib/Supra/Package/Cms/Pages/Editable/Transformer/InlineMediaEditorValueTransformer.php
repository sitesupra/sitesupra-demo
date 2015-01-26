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
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class InlineMediaEditorValueTransformer implements ValueTransformerInterface, ContainerAware, BlockPropertyAware
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

        if (empty($value['type'])) {
            throw new TransformationFailedException(
                'Type is not specified.'
            );
        }

        switch ($value['type']) {
            case 'media':
            case 'video':
                return $this->reverseTransformMediaData($value);

            case 'image':
                return $this->reverseTransformImageData($value);

            default:
                throw new TransformationFailedException(sprintf(
                    'Unrecognized type [%s].', $value['type']
                ));
        }
    }

    /**
     * @param array $data
     * @return null
     */
    protected function reverseTransformImageData(array $data)
    {
        $element = new ImageReferencedElement();

        // @TODO: some data validation must happen here.
        $element->fillFromArray($data);

        $this->getMediaMetadata()->setReferencedElement($element);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function reverseTransformMediaData(array $data)
    {
        if (! isset($data['url'])) {
            throw new TransformationFailedException('Media URL is missing.');
        }

        $mediaEmbed = $this->container['cms.media_embed'];
        /* @var $mediaEmbed MediaEmbed */

        $mediaObject = $mediaEmbed->parseUrl($data['url']);

        if ($mediaObject === null) {
            throw new TransformationFailedException(sprintf(
                'Failed to parse media URL [%s].',
                $data['url']
            ));
        }

        $mediaElement = new MediaReferencedElement();

        $mediaElement->setUrl($data['url']);

        $this->getMediaMetadata()->setReferencedElement($mediaElement);

        if (isset($data['width'])) {

            $width = (int) $data['width'];

            if ($width < 1 ) {
                throw new TransformationFailedException(sprintf(
                    'Invalid width value: [%s]', $data['width']
                ));
            }

            $mediaElement->setWidth($width);
        }

        if (isset($data['height'])) {

            $height = (int) $data['height'];

            if ($height < 1 ) {
                throw new TransformationFailedException(sprintf(
                    'Invalid height value: [%s]', $data['height']
                ));
            }

            $mediaElement->setHeight($height);
        }

        return $data['url'];
    }

    /**
     * @param mixed $value
     * @return null|array
     */
    public function transform($value)
    {
        $metadata = $this->property->getMetadata();

        if (! $metadata->offsetExists('media')) {
            return null;
        }

        $element = $metadata->get('media')->getReferencedElement();
        /* @var $element ReferencedElementAbstract */

        if ($element === null) {
            return null;
        }

        $elementData = $element->toArray();

        if ($element instanceof ImageReferencedElement) {
            $fileStorage = $this->container['cms.file_storage'];
            /* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

            $image = $fileStorage->findImage($element->getImageId());

            if ($image === null) {
                return null;
            }

            $elementData['image'] = $fileStorage->getFileInfo($image);
        }

        return $elementData;
    }

    /**
     * @return BlockPropertyMetadata
     */
    private function getMediaMetadata()
    {
        if (! $this->property->getMetadata()->offsetExists('media')) {
            $this->property->addMetadata(new BlockPropertyMetadata('media', $this->property));
        }

        return $this->property->getMetadata()->get('media');
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
