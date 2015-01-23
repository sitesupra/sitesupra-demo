<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface;

class KeywordsFilter implements FilterInterface
{
    /**
     * {@inheritDoc}
     * @return array
     */
    public function filter($content, array $options = array())
    {
        if (empty($content) || ! is_string($content)) {
            return array();
        }

        return explode(';', $content);
    }
}