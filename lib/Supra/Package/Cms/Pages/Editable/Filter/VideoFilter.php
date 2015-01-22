<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use MediaEmbed\MediaEmbed;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
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
        if (empty($content) || ! is_string($content)) {
            return null;
        }

        $mediaEmbed = $this->container['cms.media_embed'];
        /* @var $mediaEmbed MediaEmbed */

        $mediaObject = $mediaEmbed->parseUrl($content);

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
