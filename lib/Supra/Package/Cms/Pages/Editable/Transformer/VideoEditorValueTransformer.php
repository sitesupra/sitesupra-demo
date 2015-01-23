<?php

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

        $mediaElement = $this->getMediaElement();

        if ($mediaElement === null) {
            $mediaElement = new MediaReferencedElement();
            $mediaElement->setUrl($value['url']);

            $metaItem = $this->property->getMetadata()->get('media');

            if ($metaItem === null) {
                $metaItem = new BlockPropertyMetadata('media', $this->property);
                $this->property->addMetadata($metaItem);
            }

            $metaItem->setReferencedElement($mediaElement);
        }

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

        return $value['url'];
    }

    /**
     * @param mixed $value
     * @return null|array
     */
    public function transform($value)
    {
        $element = $this->getMediaElement();
        return $element ? $element->toArray() : null;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return MediaReferencedElement|null
     */
    private function getMediaElement()
    {
        if (! $this->property->getMetadata()->offsetExists('media')) {
            return null;
        }

        $element = $this->property->getMetadata()->get('media');

        if ($element instanceof MediaReferencedElement) {
            return $element;
        }

        return null;
    }
}
