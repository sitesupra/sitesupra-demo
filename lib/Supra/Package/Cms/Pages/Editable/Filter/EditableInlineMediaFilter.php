<?php

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
