<?php

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
