<?php

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
