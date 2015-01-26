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

namespace Supra\Package\Cms\Pages\Twig;

use Twig_Node_Expression_Constant as ConstantNode;
use Twig_Node_Expression_Array as ArrayNode;

class BlockPropertySetNode extends AbstractPropertyFunctionNode
{
    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'property set';
    }

    /**
     * {@inheritDoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getNameOptionValue() . "')");
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        $node = $this->getOptionsArgumentNode();

        if ($node === null) {
            throw new \RuntimeException('No arguments for set provided.');
        }

        if (! $node instanceof ArrayNode) {
            throw new \RuntimeException(sprintf(
                'Expecting options argument to be an array, [%s] received.',
                get_class($node)
            ));
        }

        return $this->nodeToArray($node);
    }

    /**
     * @return string|null
     */
    public function getLabelOptionValue()
    {
        $options = $this->getOptions();

        if (isset($options['label']) && $options['label'] instanceof ConstantNode) {
            return $options['label']->getAttribute('value');
        }

        return null;
    }
}
