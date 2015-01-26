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
